<?php
require_once __DIR__ . '/../config/Database.php';

echo "Actualizando base de datos para la Bodega...\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Crear tabla de marcas
    $db->exec("CREATE TABLE IF NOT EXISTS brands (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE
    )");

    // Agregar brand_id a la tabla de productos si no existe
    try {
        $db->exec("ALTER TABLE products ADD COLUMN brand_id INTEGER REFERENCES brands(id) ON DELETE SET NULL");
    } catch (PDOException $e) {
        // Es probable que la columna ya exista, ignoramos el error.
    }

    // Insertar categorías predeterminadas
    $categories = [
        'Arroz', 'Pasta', 'Harina', 'Granos', 
        'Condimentos y Salsas', 'Aceites', 
        'Enlatados y Conservas', 'Bebidas', 'Snacks', 'Limpieza'
    ];
    $stmtCat = $db->prepare("INSERT OR IGNORE INTO categories (name) VALUES (:name)");
    foreach ($categories as $cat) {
        $stmtCat->execute(['name' => $cat]);
    }

    // Insertar marcas predeterminadas de ejemplo
    $brands = [
        'Mary', 'Primor', 'Casa', 'La Lucha', 
        'Ronco', 'Capri', 'Allegri', 'Divella', 
        'P.A.N.', 'Juana', 'Robin Hood', 'Caledonia', 
        'Goya', 'Empresas Polar', 'Heinz', 'Pampero', 
        'La Campagnola', 'Mavesa', 'Kraft', 'Hellmann\'s', 
        'McCormick', 'Natura\'s', 'Margarita', 'Eveba', 
        'Van Camp\'s', 'Del Monte', 'Aunt Lucy', 'La Constancia', 'La Gaviota'
    ];
    $stmtBrand = $db->prepare("INSERT OR IGNORE INTO brands (name) VALUES (:name)");
    foreach ($brands as $brand) {
        $stmtBrand->execute(['name' => $brand]);
    }

    echo "Base de datos actualizada correctamente para la Bodega.\n";
} catch (Exception $e) {
    die("Error al actualizar la base de datos: " . $e->getMessage() . "\n");
}
