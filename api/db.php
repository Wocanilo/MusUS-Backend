<?php
// docker-php-ext-install pdo pdo_mysql
$dsn = 'mysql:dbname='.getenv("MUSUS_DB_NAME").';host='.getenv("MUSUS_DB_HOST");
$user = getenv("MUSUS_DB_USER");
$password = getenv("MUSUS_DB_PASSWORD");

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