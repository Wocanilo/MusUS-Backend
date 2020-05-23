<?php
if($_SERVER['HTTP_ORIGIN'] == "https://musus.wocat.xyz"){
    header("Access-Control-Allow-Origin: https://musus.wocat.xyz");
}else if($_SERVER['HTTP_ORIGIN'] == "http://localhost:8082"){
    header("Access-Control-Allow-Origin: http://localhost:8082");
}

session_start();
session_unset();
session_destroy();

header('Location: /');
?>