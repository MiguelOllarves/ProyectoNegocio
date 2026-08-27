<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock server env
$_SERVER['DOCUMENT_ROOT'] = __DIR__;
$_SESSION['user_id'] = 1;
$_SESSION['business_id'] = 1;

require __DIR__.'/config/Database.php';
require __DIR__.'/modules/inventory/models/Product.php';
require __DIR__.'/modules/purchases/models/Purchase.php';
require __DIR__.'/modules/inventory/models/ProductPresentation.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Crear producto Arroz
    $db->exec("INSERT INTO products (name, stock, unit_cost, price) VALUES ('Arroz Test', 0, 0, 1.5)");
    $productId = $db->lastInsertId();
    
    // Get a valid unit ID from the DB
    $unit = $db->query("SELECT id FROM units_of_measure LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $unitId = $unit ? $unit['id'] : 1;

    // 2. Crear Presentaciones
    $presModel = new ProductPresentation();
    $pid1 = $presModel->create(['product_id' => $productId, 'name' => 'Caja 20 und', 'quantity' => 20, 'unit_id' => $unitId]);
    $pid2 = $presModel->create(['product_id' => $productId, 'name' => 'Paquete 5 und', 'quantity' => 5, 'unit_id' => $unitId]);
    
    // 3. Comprar Arroz - 1 Caja x $20
    $purchaseModel = new Purchase();
    $items = [
        [
            'product_id' => $productId,
            'presentation_id' => $pid1,
            'quantity' => 1,
            'cost' => 20
        ]
    ];
    $purchaseId = $purchaseModel->createWithItems(1, null, $items, "Compra de prueba de 1 Caja de Arroz Test a $20");

    // 4. Check results
    $product = $db->query("SELECT * FROM products WHERE id = $productId")->fetch(PDO::FETCH_ASSOC);
    
    echo "=== RESULTADOS DE COMPRA ===\n";
    echo "Stock Total: " . $product['stock'] . "\n";
    echo "Expectativa Stock: 20\n";
    echo "Costo Unitario Base: " . $product['unit_cost'] . "\n";
    echo "Expectativa Costo Unitario Base: 1 (20 / 20 = 1)\n";
    
    $purchases = $db->query("SELECT * FROM purchase_items WHERE purchase_id = $purchaseId")->fetchAll(PDO::FETCH_ASSOC);
    echo "Items Comprados:\n";
    print_r($purchases);
    
    // Clean up
    $db->exec("DELETE FROM kardex WHERE product_id = $productId");
    $db->exec("DELETE FROM purchase_items WHERE purchase_id = $purchaseId");
    $db->exec("DELETE FROM purchases WHERE id = $purchaseId");
    $db->exec("DELETE FROM product_presentations WHERE product_id = $productId");
    $db->exec("DELETE FROM products WHERE id = $productId");
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
