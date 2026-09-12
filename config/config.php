<?php
// ============================================================
// Configuración Global del Sistema - Tu Inventario
// ============================================================

// --- Cargar variables de entorno desde .env ---
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Cargar .env desde la raíz del proyecto
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

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
    if (is_array($dbOpts) && !empty($dbOpts['host'])) {
        define('DB_HOST', $dbOpts['host']);
        define('DB_PORT', $dbOpts['port'] ?? 5432);
        define('DB_USER', $dbOpts['user'] ?? 'postgres');
        define('DB_PASS', $dbOpts['pass'] ?? '');
        define('DB_NAME', ltrim($dbOpts['path'] ?? '/', '/'));
    } else {
        // Fallback si DATABASE_URL llegó vacía o mal formateada
        define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
        define('DB_PORT', getenv('DB_PORT') ?: 5432);
        define('DB_USER', getenv('DB_USER') ?: 'postgres');
        define('DB_PASS', getenv('DB_PASS') ?: '');
        define('DB_NAME', getenv('DB_NAME') ?: 'tu_inventario');
    }
} else {
    // Fallback para desarrollo local - NUNCA hardcodear credenciales reales
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ?: 5432);
    define('DB_USER', getenv('DB_USER') ?: 'postgres');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'tu_inventario');
}

// --- Configuración de URL Base (Dinámica Prod/Dev) ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https://" : "http://";

$appUrl = getenv('APP_URL') ?: getenv('NEXT_PUBLIC_APP_URL') ?: '';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$host = preg_replace('/^www\./i', '', $host);

if (!empty($appUrl)) {
    $parsedAppUrl = parse_url($appUrl);
    if (!empty($parsedAppUrl['host'])) {
        $host = preg_replace('/^www\./i', '', $parsedAppUrl['host']);
    }
}

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$baseDir = str_replace('\\', '/', dirname($scriptName));
if ($baseDir === '/' || $baseDir === '\\' || getenv('VERCEL') == '1') {
    $baseDir = '';
}

if (!empty($appUrl)) {
    $base_url = rtrim($appUrl, '/') . '/';
} else {
    $base_url = $protocol . $host . $baseDir . '/';
}

define('BASE_URL', $base_url);

// --- Configuración de la Aplicación ---
define('APP_NAME', 'Tu Inventario');
define('APP_VERSION', '1.0.0');

// --- Configuración de Suscripción ---
define('SUBSCRIPTION_PRICE_USD', (float)(getenv('SUBSCRIPTION_PRICE_USD') ?: '3.00'));
define('SUBSCRIPTION_CURRENCY', 'USD');

// --- Zona Horaria ---
date_default_timezone_set('America/Caracas');

// --- Detección de Vercel ---
define('IS_VERCEL', getenv('VERCEL') === '1' || isset($_SERVER['VERCEL']));

// --- Performance Settings ---
define('ENABLE_GZIP', function_exists('ob_gzhandler'));
define('CACHE_STATIC_ASSETS', true); // CSS/JS/images - 1 year immutable
define('CACHE_DYNAMIC_CONTENT', false); // HTML pages - no cache for auth'd content
define('QUERY_CACHE_ENABLED', false); // Set true if using Redis/Memcached

// --- Supabase Connection Pool ---
define('DB_POOL_MIN', 1);
define('DB_POOL_MAX', 5); // Keep low for free tier

// --- Configuración SMTP (Para correos) ---
define('SMTP_HOST', getenv('SMTP_HOST') ?: '');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: '');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Tu Inventario');

// --- Configuración WebPush (VAPID) - DEBE estar en variables de entorno ---
$vapidPub = getenv('VAPID_PUBLIC_KEY');
$vapidPriv = getenv('VAPID_PRIVATE_KEY');
if (empty($vapidPub) || empty($vapidPriv)) {
    error_log("WARNING: VAPID keys not configured in environment variables");
}
define('VAPID_PUBLIC_KEY', $vapidPub ?: '');
define('VAPID_PRIVATE_KEY', $vapidPriv ?: '');

// --- Analíticas (Google Tag Manager) ---
define('GTM_ID', getenv('GTM_ID') ?: 'GTM-NHRNGKB2');

// --- Versionado de CSS (cache-busting): Vercel sirve css/* con Cache-Control immutable 1 año.
// --- Sin versión en la URL, los navegadores jamás recargan tailwind.css tras un redeploy.
$cssFile = dirname(__DIR__) . '/public/css/tailwind.css';
define('CSS_VERSION', is_file($cssFile) ? (string) @filemtime($cssFile) : '1');
