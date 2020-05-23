<?php
require_once "db.php";

try {
    $sqlFile = file_get_contents("../repo/db.sql");

    $conn->exec($sqlFile);

    echo "Installed";
} catch (PDOException $e) {
    die('Database exists');
}


?>