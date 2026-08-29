<?php
require_once __DIR__ . '/config/config.php';
$db = new PDO("pgsql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
$stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'expenses'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
