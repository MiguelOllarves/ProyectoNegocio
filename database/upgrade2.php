<?php
require_once __DIR__ . '/../config/Database.php';
$db = Database::getInstance()->getConnection();

echo "Iniciando migración...\n";

// 1. Modificar tabla sales si es que SQLite lo permite a través de exec
try {
    $db->exec("ALTER TABLE sales ADD COLUMN subtotal REAL DEFAULT 0;");
    $db->exec("ALTER TABLE sales ADD COLUMN iva REAL DEFAULT 0;");
    $db->exec("ALTER TABLE sales ADD COLUMN igtf REAL DEFAULT 0;");
} catch (\Exception $e) {
    // Si ya existen las columnas o sqlite no lo permitió, continuar.
    echo "Info (Columnas sales): " . $e->getMessage() . "\n";
}

// 2. Pagos de Ventas (Mixtos)
$db->exec("
CREATE TABLE IF NOT EXISTS ventas_pagos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    venta_id INTEGER,
    metodo_pago TEXT, 
    monto_divisa REAL DEFAULT 0,
    monto_bs REAL DEFAULT 0,
    tasa_aplicada REAL,
    FOREIGN KEY (venta_id) REFERENCES sales(id) ON DELETE CASCADE
);
");

// 3. Arqueo de Caja
$db->exec("
CREATE TABLE IF NOT EXISTS arqueo_caja (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    fecha_apertura DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME,
    monto_inicial_usd REAL DEFAULT 0,
    monto_inicial_bs REAL DEFAULT 0,
    ventas_usd REAL DEFAULT 0,
    ventas_bs REAL DEFAULT 0,
    declarado_usd REAL DEFAULT 0,
    declarado_bs REAL DEFAULT 0,
    diferencia_usd REAL DEFAULT 0,
    diferencia_bs REAL DEFAULT 0,
    estado TEXT DEFAULT 'abierta'
);
");

echo "Migración completada.\n";
