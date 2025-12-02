<?php 

//Required for improved SEO
function create_breadcrumbs() {
    $url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $url = trim($url, '/');

    //Split url path into strings and remove white spaces
    $url_str= array_filter(explode('/', $url));

    //Stores breadcrumbs (label and url)
    $breadcrumbs = [];

    //Initialise to track path to proper link
    $path = "";

    //Build dynamic breadcrumbs
    foreach($url_str as $i => $crumb){
        $path .= '/' .$crumb;
        $label = ucwords(str_replace(['.php', '-', '_'], ' ', $crumb));

        //If not on last index, display a link
        if ($i !== count($url_str)-1) {
            $breadcrumbs [] = ["label" => $label,
                                "url" => $path
            ];
        }
        //Otherwise display as text
        else {
            $breadcrumbs [] = ["label" => $label,
                                "url" => null
            ];
        }
    }
        
    //Adds query values to breadcrumbs
    if (!empty($_GET)) {
        foreach ($_GET as $value) {
            $label= ucwords(htmlspecialchars($value));
            $breadcrumbs [] = [ "label" => $label,
                                 "url" => null
            ];
        }
    }
    return $breadcrumbs;
}
?>

<!DOCTYPE html>
<body>
    <header class="sticky-top  navbar-expand-lg">
        <div class="container-fluid">
            <!--Logo-->
            <a href="/" class="logo">Bibliohaha</a>

            <!--Links-->
            <nav class="navbar">
                <ul>
                    <li><a href="index.php" class="<?php echo ($activemenu=='home') ? 'active':''; ?>"> home </a></li>
                    <li><a href="contact&about.php" class="<?php echo ($activemenu=='contact&about') ? 'active':''; ?>">about & contact</a></li>
                </ul>
            </nav>
            <!--btns-->
            <div class="icons">
                <a class="owner_access " href="owner_dashboard.php"><i class="bi bi-sliders"> owner access</i></a>
                <a href="login.php" class="<?php echo ($activemenu=='login') ? 'active':''; ?>" ><i class="bi bi-person"></i></a>
                <a href="cart.php" class="<?php echo ($activemenu=='cart') ? 'active':''; ?>"> <i class="bi bi-cart2"></i> </a>
            </div>
        </div>
    </header>

    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <?php
                //Display breadcrumbs on pages except home page.
                if (basename($_SERVER["PHP_SELF"]) !== "index.php") {
                    $breadcrumbs = create_breadcrumbs();
                    foreach ($breadcrumbs as $crumb){
                        if ($crumb['url']) {
                            echo '<li class="breadcrumb-item"><a style="text-decoration: none; color: #8e44ad;" href="' .$crumb['url']. '">' .$crumb['label']. '</a></li>';
                        } else {
                            echo '<li class="breadcrumb-item active" aria-current="page">' .$crumb['label']. '</li>';
                        }
                    }
                }
                ?>
            </ol>
            </nav>
    </div>
</body>
