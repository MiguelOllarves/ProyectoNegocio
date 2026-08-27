<?php
require_once __DIR__ . '/../config/Database.php';
$db = Database::getInstance()->getConnection();

$products = $db->query("SELECT * FROM products ORDER BY id DESC LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $p) {
    echo "--- PRODUCT " . $p['id'] . " ---\n";
    echo "Name: " . $p['name'] . "\n";
    echo "Price: " . $p['price'] . "\n";
    echo "Unit Cost: " . $p['unit_cost'] . "\n";
    echo "Margin: " . $p['profit_margin'] . "\n";
    echo "Stock (raw): " . $p['stock'] . "\n";
    echo "Sale Unit ID: " . $p['sale_unit_id'] . "\n";
    
    echo "  [META]\n";
    $metas = $db->query("SELECT * FROM product_meta WHERE product_id = " . $p['id'])->fetchAll(PDO::FETCH_ASSOC);
    foreach ($metas as $m) echo "  " . $m['meta_key'] . " = " . $m['meta_value'] . "\n";
    
    echo "  [PRESENTATIONS]\n";
    $press = $db->query("SELECT * FROM product_presentations WHERE product_id = " . $p['id'])->fetchAll(PDO::FETCH_ASSOC);
    foreach ($press as $pr) echo "  " . $pr['name'] . " | Qty: " . $pr['quantity'] . " | Unit: " . $pr['unit_id'] . "\n";
    echo "\n";
}
