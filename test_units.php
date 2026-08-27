<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__.'/core/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check sale_units
    $stmt = $db->query("SELECT * FROM sale_units");
    $sale_units = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : "Table not found";
    
    // Check units_of_measure
    $stmt2 = $db->query("SELECT * FROM units_of_measure");
    $uom = $stmt2 ? $stmt2->fetchAll(PDO::FETCH_ASSOC) : "Table not found";
    
    echo "=== SALE UNITS ===\n";
    print_r($sale_units);
    
    echo "=== UNITS OF MEASURE ===\n";
    print_r($uom);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
