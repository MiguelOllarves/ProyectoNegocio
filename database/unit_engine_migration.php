<?php
require_once __DIR__ . '/../config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $db->beginTransaction();

    echo "--- INICIANDO MIGRACION DE UNIDADES ---\n";

    // 1. Recrear units_of_measure con la nueva estructura
    $db->exec("DROP TABLE IF EXISTS units_of_measure");
    
    $autoInc = $driver === 'pgsql' ? 'SERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    
    $db->exec("CREATE TABLE units_of_measure (
        id $autoInc,
        name VARCHAR(50) NOT NULL,
        abbreviation VARCHAR(20) NOT NULL,
        base_type VARCHAR(20) NOT NULL, -- 'peso', 'volumen', 'unidad'
        base_unit_id INTEGER,
        conversion_to_base REAL DEFAULT 1.0
    )");

    echo "Tabla units_of_measure recreada.\n";

    // 2. Insertar unidades estándar
    $stmt = $db->prepare("INSERT INTO units_of_measure (id, name, abbreviation, base_type, base_unit_id, conversion_to_base) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Base units
    $stmt->execute([1, 'Gramo', 'g', 'peso', 1, 1.0]);
    $stmt->execute([2, 'Mililitro', 'ml', 'volumen', 2, 1.0]);
    $stmt->execute([3, 'Unidad', 'und', 'unidad', 3, 1.0]);
    
    // Derived units - Weight
    $stmt->execute([4, 'Kilogramo', 'kg', 'peso', 1, 1000.0]);
    $stmt->execute([8, 'Miligramo', 'mg', 'peso', 1, 0.001]);

    // Derived units - Volume
    $stmt->execute([9, 'Litro', 'L', 'volumen', 2, 1000.0]);
    $stmt->execute([10, 'Caja 12L', 'cj12L', 'volumen', 2, 12000.0]);
    $stmt->execute([11, 'Galón', 'gal', 'volumen', 2, 3785.41]);
    
    // Derived units - Count
    $stmt->execute([12, 'Caja', 'cj', 'unidad', 3, 1.0]); 
    $stmt->execute([13, 'Bulto', 'bulto', 'unidad', 3, 1.0]);
    $stmt->execute([14, 'Paquete', 'pqte', 'unidad', 3, 1.0]);

    echo "Unidades base insertadas.\n";

    // 3. Modificar tabla products
    $columns = [
        "measurement_type VARCHAR(20) DEFAULT 'unidad'", // peso, volumen, unidad
        "base_unit_id INTEGER",
        "purchase_unit_id INTEGER",
        "content_per_purchase REAL DEFAULT 1.0",
        "contained_unit_id INTEGER",
        "sale_unit_id INTEGER"
    ];
    
    foreach ($columns as $col) {
        try {
            $db->exec("ALTER TABLE products ADD COLUMN " . $col);
        } catch (Exception $e) {
            // Ignore if exists
        }
    }
    echo "Nuevas columnas de unidades agregadas a products.\n";

    // 4. Modificar tablas transaccionales (quantity -> REAL)
    if ($driver === 'pgsql') {
        try {
            $db->exec("ALTER TABLE sale_items ALTER COLUMN quantity TYPE NUMERIC USING quantity::NUMERIC");
            $db->exec("ALTER TABLE purchase_items ALTER COLUMN quantity TYPE NUMERIC USING quantity::NUMERIC");
            $db->exec("ALTER TABLE kardex ALTER COLUMN quantity TYPE NUMERIC USING quantity::NUMERIC");
            $db->exec("ALTER TABLE kardex ALTER COLUMN stock_after TYPE NUMERIC USING stock_after::NUMERIC");
            echo "Tipos de columna quantity actualizados a NUMERIC en PostgreSQL.\n";
        } catch (Exception $e) {
            echo "Aviso: No se pudo modificar el tipo de columna en PG: " . $e->getMessage() . "\n";
        }
    }
    
    // Añadimos columnas extra de trazabilidad
    $traceCols = [
        "sale_items" => ["normalized_quantity REAL DEFAULT 0", "unit_id INTEGER"],
        "purchase_items" => ["normalized_quantity REAL DEFAULT 0", "unit_id INTEGER"],
        "kardex" => ["normalized_quantity REAL DEFAULT 0", "unit_id INTEGER"]
    ];
    
    foreach ($traceCols as $table => $cols) {
        foreach ($cols as $col) {
            try {
                $db->exec("ALTER TABLE $table ADD COLUMN $col");
            } catch (Exception $e) {
                // Ignore if exists
            }
        }
    }
    echo "Columnas de trazabilidad agregadas.\n";
    
    // 5. Migrar productos existentes al nuevo esquema infiriendo su 'unit_of_measure'
    $stmtProds = $db->query("SELECT id, unit_of_measure FROM products");
    $updateProd = $db->prepare("UPDATE products SET measurement_type = ?, base_unit_id = ?, purchase_unit_id = ?, sale_unit_id = ? WHERE id = ?");
    
    while ($prod = $stmtProds->fetch(PDO::FETCH_ASSOC)) {
        $uom = strtolower($prod['unit_of_measure'] ?? '');
        $mType = 'unidad';
        $baseId = 3; // und
        $saleId = 3; // und
        
        if (strpos($uom, 'kg') !== false || strpos($uom, 'kilo') !== false) {
            $mType = 'peso'; $baseId = 1; $saleId = 4; // kg
        } elseif (strpos($uom, 'gramo') !== false || strpos($uom, 'g') !== false) {
            $mType = 'peso'; $baseId = 1; $saleId = 1; // g
        } elseif (strpos($uom, 'litro') !== false || strpos($uom, 'l') !== false) {
            $mType = 'volumen'; $baseId = 2; $saleId = 9; // L
        } elseif (strpos($uom, 'ml') !== false || strpos($uom, 'mili') !== false) {
            $mType = 'volumen'; $baseId = 2; $saleId = 2; // ml
        } elseif (strpos($uom, 'saco') !== false || strpos($uom, 'sc') !== false) {
            $mType = 'peso'; $baseId = 1; $saleId = 4; // Asume que se vende por kg si es saco
        }
        
        $updateProd->execute([$mType, $baseId, $saleId, $saleId, $prod['id']]);
    }
    
    echo "Productos existentes migrados al nuevo esquema de unidades.\n";

    $db->commit();
    echo "--- MIGRACION DE UNIDADES COMPLETADA CON EXITO ---\n";
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "ERROR FATAL: " . $e->getMessage() . "\n";
}
