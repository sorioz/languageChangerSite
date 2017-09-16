<?php
header("Content-type: text/html; charset=utf-8");
@session_start();

$GLOBALS['lang'] = include 'functions/setlang.php';
include_once 'functions/gettext.php';

$urlParts = explode('/', $_SERVER['REQUEST_URI']);
if (count($urlParts) > 0) {
    $url = preg_replace("#[^a-z0-9-/]+#i", "", $_SERVER['REQUEST_URI']);
    if (is_file("pages/" . $url . ".php")) {
        $GLOBALS['page'] = $url;
    } else {
        $GLOBALS['page'] = "index";
    }
} else {
    $GLOBALS['page'] = "index";
}
include_once 'components/header.php';
include_once 'pages/' . $page . '.php';
include_once 'components/footer.php';
