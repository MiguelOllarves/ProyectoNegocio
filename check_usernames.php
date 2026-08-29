<?php
require_once __DIR__ . '/config/config.php';
$db = new PDO("pgsql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
$stmt = $db->query("SELECT username FROM users LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
