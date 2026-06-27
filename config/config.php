<?php
// ============================================================
// Configuración Global del Sistema - TuInventarioApp ERP
// ============================================================

// --- Configuración de Base de Datos Interactiva (Agnóstica) ---
// Leer enlace de Supabase de las variables de entorno (Para Vercel)
$dbUrl = getenv('DATABASE_URL');

if ($dbUrl) {
    // Modo Nube (PostgreSQL en Vercel)
    define('DB_DRIVER', 'pgsql');
    $dbOpts = parse_url($dbUrl);
    define('DB_HOST', $dbOpts["host"]);
    define('DB_PORT', $dbOpts["port"] ?? 5432);
    define('DB_USER', $dbOpts["user"]);
    define('DB_PASS', $dbOpts["pass"]);
    define('DB_NAME', ltrim($dbOpts["path"], '/'));
} else {
    // Modo Local (SQLite local)
    define('DB_DRIVER', 'sqlite'); 
    define('DB_PATH', __DIR__ . '/../database/tu_inventario.db');
}

// --- Configuración de URL Base (Dinámica Prod/Dev) ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https://" : "http://";

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$baseDir = str_replace('\\', '/', dirname($scriptName));
if ($baseDir === '/' || $baseDir === '\\' || getenv('VERCEL') == '1') {
    $baseDir = '';
}
$base_url = $protocol . $host . $baseDir . '/';

define('BASE_URL', $base_url);

// --- Configuración de la Aplicación ---
define('APP_NAME', 'TuInventarioApp');
define('APP_VERSION', '2.0.0');
