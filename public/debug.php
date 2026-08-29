<?php
require_once __DIR__ . '/../config/Database.php';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, name, unit_cost, base_unit_id, sale_unit_id FROM products WHERE is_dish = FALSE");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents(__DIR__ . '/debug_output.json', json_encode($data, JSON_PRETTY_PRINT));
    echo "OK";
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/debug_output.json', "ERROR: " . $e->getMessage());
    echo "ERROR";
}
