<?php
header("Content-type: text/html; charset=utf-8");
@session_start();

include_once 'functions/setlang.php';
include_once 'functions/gettext.php';

$_SESSION['URL'] = explode('/', $_GET['req']);
if (count($_SESSION['URL']) > 0) {
    $url = preg_replace("#[^a-z0-9-/]+#i", "", $_GET['req']);
    if (is_file("pages/" . $url . ".php")) {
        $_SESSION['page'] = $url;
    } else {
        $_SESSION['page'] = "index";
    }
} else {
    $_SESSION['page'] = "index";
}
include_once 'components/header.php';
include_once 'pages/' . $_SESSION['page'] . '.php';
include_once 'components/footer.php';


?>