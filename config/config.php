<?php
// ============================================================
// Configuración Global del Sistema - Tu Inventario
// ============================================================

// --- Auto copy logos to public ---
$publicIconsDir = __DIR__ . '/../public/iconos_negocio';
if (!file_exists($publicIconsDir)) {
    @mkdir($publicIconsDir, 0777, true);
}
if (!file_exists($publicIconsDir . '/logo1-t.png') && file_exists(__DIR__ . '/../iconos_negocio/logo1-t.png')) {
    @copy(__DIR__ . '/../iconos_negocio/logo1-t.png', $publicIconsDir . '/logo1-t.png');
    @copy(__DIR__ . '/../iconos_negocio/logo1-t.ico', $publicIconsDir . '/logo1-t.ico');
}

// --- Configuración de Base de Datos (PostgreSQL Exclusivo) ---
// Leer enlace de la base de datos de las variables de entorno
$dbUrl = getenv('DATABASE_URL');

define('DB_DRIVER', 'pgsql');

if ($dbUrl) {
    $dbOpts = parse_url($dbUrl);
    define('DB_HOST', $dbOpts["host"]);
    define('DB_PORT', $dbOpts["port"] ?? 5432);
    define('DB_USER', $dbOpts["user"]);
    define('DB_PASS', $dbOpts["pass"]);
    define('DB_NAME', ltrim($dbOpts["path"], '/'));
} else {
    // Fallback para desarrollo local si no hay DATABASE_URL
    define('DB_HOST', 'localhost');
    define('DB_PORT', 5432);
    define('DB_USER', 'postgres');
    define('DB_PASS', 'postgres');
    define('DB_NAME', 'tu_inventario');
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
define('APP_NAME', 'Tu Inventario');
define('APP_VERSION', '0.0.0');

// --- Zona Horaria ---
date_default_timezone_set('America/Caracas');

// --- Configuración SMTP (Para correos) ---
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: '');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Tu Inventario');

// --- Configuración WebPush (VAPID) ---
define('VAPID_PUBLIC_KEY', getenv('VAPID_PUBLIC_KEY') ?: '');
define('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY') ?: '');
