<?php
if($_SERVER['HTTP_ORIGIN'] == "https://musus.wocat.xyz"){
    header("Access-Control-Allow-Origin: https://musus.wocat.xyz");
}else if($_SERVER['HTTP_ORIGIN'] == "http://localhost:8080"){
    header("Access-Control-Allow-Origin: http://localhost:8080");
}else if($_SERVER['HTTP_ORIGIN'] == "http://musus.wocat.xyz"){
    header("Access-Control-Allow-Origin: http://musus.wocat.xyz");
}

session_start();
session_unset();
session_destroy();

header('Location: /');
?>