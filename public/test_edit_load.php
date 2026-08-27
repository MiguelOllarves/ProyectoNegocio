<?php
require_once __DIR__ . '/../config/Database.php';
$db = Database::getInstance()->getConnection();

$res = $db->query("SELECT p.id as pid, p.name as pname, pr.name as prname, pr.quantity, p.sale_unit_id FROM products p JOIN product_presentations pr ON p.id=pr.product_id")->fetchAll(PDO::FETCH_ASSOC);

print_r($res);
?>
