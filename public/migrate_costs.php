<?php
require_once __DIR__ . '/../config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Buscar todos los insumos en kitchen_ingredients que tengan costo
    $stmt = $db->query("SELECT name, cost_per_unit, unit_id, stock, min_stock, supplier_id, tenant_id FROM kitchen_ingredients WHERE cost_per_unit > 0");
    $old_ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    
    foreach ($old_ingredients as $old) {
        // Actualizar en products buscando por nombre y tenant_id
        $updateStmt = $db->prepare("
            UPDATE products 
            SET unit_cost = :cost,
                base_unit_id = :unit_id,
                sale_unit_id = :unit_id,
                stock = :stock,
                min_stock = :min_stock,
                supplier_id = :supplier_id
            WHERE name = :name 
              AND tenant_id = :tenant_id 
              AND is_dish = FALSE
        ");
        
        $updateStmt->execute([
            'cost' => $old['cost_per_unit'],
            'unit_id' => $old['unit_id'],
            'stock' => $old['stock'],
            'min_stock' => $old['min_stock'],
            'supplier_id' => $old['supplier_id'],
            'name' => $old['name'],
            'tenant_id' => $old['tenant_id']
        ]);
        
        $updated += $updateStmt->rowCount();
    }
    
    echo "<h1>Migración completada</h1>";
    echo "<p>Se actualizaron {$updated} productos con los costos de kitchen_ingredients.</p>";
    echo "<a href='../restaurant/insumos'>Volver a Insumos</a>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
