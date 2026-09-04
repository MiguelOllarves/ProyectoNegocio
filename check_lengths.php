<?php
require 'config/config.php';
require 'config/Database.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT key, length(value) as len FROM settings ORDER BY len DESC LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
