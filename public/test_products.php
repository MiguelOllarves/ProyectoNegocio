<?php
require_once __DIR__ . '/config/Database.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, name, unit_of_measure, measurement_type, base_unit_id FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($products);
echo "</pre>";
