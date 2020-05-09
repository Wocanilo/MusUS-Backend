<?php
// docker-php-ext-install pdo pdo_mysql
$dsn = 'mysql:dbname=musus;host=127.0.0.1';
$user = 'dbuser';
$password = 'dbpass';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    //echo 'Connection failed: ' . $e->getMessage();
    die('Connection failed');
}

?>