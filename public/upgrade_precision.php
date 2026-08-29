<?php
require_once __DIR__ . '/../config/Database.php';
$db = Database::getInstance()->getConnection();
try {
    $db->exec("ALTER TABLE products ALTER COLUMN unit_cost TYPE NUMERIC(15,6)");
    $db->exec("ALTER TABLE products ALTER COLUMN bulk_cost TYPE NUMERIC(15,6)");
    $db->exec("ALTER TABLE kitchen_ingredients ALTER COLUMN cost_per_unit TYPE NUMERIC(15,6)");
    $db->exec("ALTER TABLE sale_items ALTER COLUMN cost_at_sale TYPE NUMERIC(15,6)");
    echo "SUCCESS: precision increased";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
