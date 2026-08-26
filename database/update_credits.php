<?php
require_once __DIR__ . '/../config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Iniciando actualización de la tabla credits...\n";

    // Intentar añadir las columnas (SQLite ignorará el error si ya existen, pero es mejor hacerlo en bloque y atrapar excepciones si las hay, aunque SQLite no tiene IF NOT EXISTS para columnas, así que hacemos un query por cada una).
    $columns = [
        "credit_type" => "TEXT DEFAULT 'producto'",
        "interest_rate" => "REAL DEFAULT 0",
        "down_payment" => "REAL DEFAULT 0",
        "base_amount" => "REAL DEFAULT 0"
    ];

    foreach ($columns as $col => $def) {
        try {
            $db->exec("ALTER TABLE credits ADD COLUMN $col $def");
            echo "Columna $col añadida con éxito.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate column name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                echo "La columna $col ya existe, saltando.\n";
            } else {
                echo "Error añadiendo columna $col: " . $e->getMessage() . "\n";
            }
        }
    }

    // Actualizar registros antiguos: el base_amount era el total_amount antes.
    $db->exec("UPDATE credits SET base_amount = total_amount WHERE base_amount = 0 OR base_amount IS NULL");
    
    echo "Actualización completada.\n";
} catch (Exception $e) {
    echo "Error crítico: " . $e->getMessage() . "\n";
}
