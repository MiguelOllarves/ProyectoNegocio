<?php
require_once __DIR__ . '/config/Database.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT p.name, p.base_unit_id, p.unit_of_measure, u.base_type FROM products p LEFT JOIN units_of_measure u ON p.base_unit_id = u.id");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
