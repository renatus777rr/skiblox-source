<?php
// db.php
declare(strict_types=1);

$DB_HOST = 'localhost';
$DB_NAME = 'skiblox';
$DB_USER = 'root';
$DB_PASS = 'r6i1P215*'; // change in prod

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
  http_response_code(500);
  exit('DB connection failed.');
}