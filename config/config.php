<?php
// ============================================================
// Configuración Global del Sistema - TuInventarioApp ERP
// ============================================================

// --- Configuración de Base de Datos ---
// Cambiar DB_DRIVER a 'pgsql' para migrar a PostgreSQL sin tocar código.
define('DB_DRIVER', 'sqlite'); // 'sqlite' | 'pgsql'

// SQLite (solo se usa si DB_DRIVER = 'sqlite')
define('DB_PATH', __DIR__ . '/../database/tu_inventario.db');

// PostgreSQL (solo se usa si DB_DRIVER = 'pgsql')
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'tu_inventario');
define('DB_USER', 'postgres');
define('DB_PASS', '');

// --- Configuración de URL Base ---
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$baseDir = str_replace('\\', '/', dirname($scriptName));
if ($baseDir === '/') {
    $baseDir = '';
}
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$base_url = 'http://' . $host . $baseDir . '/';

define('BASE_URL', $base_url);

// --- Configuración de la Aplicación ---
define('APP_NAME', 'TuInventarioApp');
define('APP_VERSION', '2.0.0');
