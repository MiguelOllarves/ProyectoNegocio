<?php
ob_start(); // Buffer all output to prevent stray warnings from corrupting JSON responses

// ==========================================
// MANEJADOR DE ERRORES VISUALES
// ==========================================
function renderVisualError($title, $message, $file = '', $line = '') {
    if (ob_get_level()) ob_clean();
    http_response_code(500);
    error_log("[ERROR] $title: $message in $file:$line");
    $html = "<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>Error del Sistema</title>";
    $html .= "<style>body{background:#f8fafc;color:#1e293b;font-family:system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1rem;} .card{background:#fff;padding:2rem;border-radius:1rem;border-top:4px solid #ef4444;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);max-width:500px;width:100%;text-align:center;} h1{color:#dc2626;margin:0 0 1rem;font-size:1.5rem;} p{margin:0 0 1rem;line-height:1.5;color:#64748b;} .btn{display:inline-block;background:#ef4444;color:#fff;text-decoration:none;padding:0.75rem 1.5rem;border-radius:0.5rem;font-weight:bold;transition:all 0.2s;border:none;cursor:pointer;} .btn:hover{background:#dc2626;}</style></head>";
    $html .= "<body><div class='card'>";
    $html .= "<h1>Error del Sistema</h1>";
    $html .= "<p>Ha ocurrido un error inesperado. Nuestro equipo ha sido notificado.</p>";
    $html .= "<div style='margin-top:1.5rem;'><a href='javascript:history.back()' class='btn' style='background:#64748b;margin-right:0.5rem;'>Volver</a><a href='/' class='btn'>Inicio</a></div>";
    $html .= "</div></body></html>";
    echo $html;
    exit;
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (error_reporting() === 0) return false;
    if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_PARSE])) {
        renderVisualError("Error Crítico de Aplicación", $errstr, $errfile, $errline);
    }
    return false; // Permitir que errores menores continúen
});

set_exception_handler(function($e) {
    renderVisualError("Excepción No Controlada", $e->getMessage(), $e->getFile(), $e->getLine());
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // En Vercel a veces los fatal errors escapan el buffer
        renderVisualError("Error Fatal de Procesamiento", $error['message'], $error['file'], $error['line']);
    }
});
// ==========================================
// HEADERS DE SEGURIDAD GLOBALES
// ==========================================
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
// CSP autocontenido: sin dependencias CDN ni dominios externos para evitar CORS/408
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com; style-src 'self' 'unsafe-inline'; font-src 'self' data:; img-src 'self' data: blob: https:; media-src 'self' data: blob: https:; connect-src 'self' https://www.google-analytics.com https://www.googletagmanager.com https://*.vercel.app; frame-src 'self' https://www.googletagmanager.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none';");
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$appUrl = getenv('APP_URL') ?: getenv('NEXT_PUBLIC_APP_URL') ?: '';
if (!empty($appUrl)) {
    $canonicalHost = parse_url($appUrl, PHP_URL_HOST);
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    if ($canonicalHost && $currentHost && strtolower($currentHost) !== strtolower($canonicalHost)) {
        $redirectUrl = rtrim($appUrl, '/') . ($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: ' . $redirectUrl, true, 301);
        exit;
    }
}

// [DEBUG FILE PROTECTION] Bloquear acceso a archivos de debug/test/reset
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$basename = basename(parse_url($requestUri, PHP_URL_PATH));
$blockedPatterns = ['test_', 'debug', 'reset_database', '.sql', '.log', 'phpinfo'];
foreach ($blockedPatterns as $pattern) {
    if (strpos($basename, $pattern) !== false) {
        http_response_code(403);
        exit('Acceso denegado.');
    }
}

// ==========================================
if (isset($_GET['serve_logo'])) {
    require_once __DIR__ . '/../config/Database.php';
    session_start();
    $tenant_id = $_SESSION['business_id'] ?? null;
    if (isset($_GET['tenant'])) $tenant_id = (int)$_GET['tenant'];
    
    // Validación de tenant: solo servir logo si hay tenant válido o sesión activa
    if ($tenant_id === null || $tenant_id <= 0) {
        $tenant_id = 1; // Default solo para landing pages públicas
    }

    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT logo_base64 FROM businesses WHERE id = ? AND logo_base64 IS NOT NULL AND logo_base64 != ''");
        $stmt->execute([$tenant_id]);
        $base64 = $stmt->fetchColumn();

        if ($base64 && strlen($base64) > 100) {
            // Validar formato antes de decodificar
            if (preg_match('/^data:image\/(jpeg|png|webp|gif);base64,/', $base64)) {
                list($type, $data) = explode(';', $base64);
                list(, $data)      = explode(',', $data);
                $imgData = base64_decode($data);
                $mime = str_replace('data:', '', $type);
                
                // Validar que la imagen sea realmente una imagen válida
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $detectedMime = $finfo->buffer($imgData);
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                
                if (in_array($detectedMime, $allowedMimes) && $detectedMime === $mime) {
                    header("Content-Type: $detectedMime");
                    header('Cache-Control: public, s-maxage=3600, max-age=3600');
                    header('X-Content-Type-Options: nosniff');
                    echo $imgData;
                    exit;
                } else {
                    // Logo no válido: limpiar automáticamente la BD
                    try {
                        $db->prepare("UPDATE businesses SET logo_base64 = NULL WHERE id = ?")->execute([$tenant_id]);
                        error_log("Logo eliminado por validación fallida: tenant $tenant_id, MIME detectado: $detectedMime");
                    } catch(Exception $e){}
                }
            }
        }
    } catch(Exception $e){
        error_log("Logo serve error: " . $e->getMessage());
    }
    
    // Fallback static - logo por defecto del sistema
    $file = __DIR__ . '/../iconos_negocio/logo1-t.png';
    if (file_exists($file)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, s-maxage=86400, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit;
    }
}

if (isset($_GET['serve_menu'])) {
    require_once __DIR__ . '/../config/Database.php';
    $tenant_id = (int)($_GET['tenant'] ?? 0);
    
    if($tenant_id > 0) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT menu_file_base64, menu_file_type FROM businesses WHERE id = ?");
            $stmt->execute([$tenant_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && !empty($row['menu_file_base64'])) {
                $base64 = $row['menu_file_base64'];
                $parts = explode(';', $base64);
                if(count($parts) > 1) {
                    $base64 = explode(',', $parts[1])[1] ?? '';
                }
                $fileData = base64_decode($base64);
                $mime = $row['menu_file_type'] ?: 'application/pdf';

                header("Content-Type: $mime");
                // Importante: No cachear a largo plazo, ya que el menú cambia, pero la URL queda igual (QR)
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                echo $fileData;
                exit;
            }
        } catch(Exception $e){}
    }
    
    header('Content-Type: text/html; charset=utf-8');
    echo "<div style='font-family:sans-serif; text-align:center; padding:50px; background:#f4f4f5; height:100vh; display:flex; align-items:center; justify-content:center;'><div><h2 style='color:#3f3f46; margin-bottom:10px;'>Menú no disponible</h2><p style='color:#71717a;'>Este negocio aún no ha actualizado su menú digital de hoy.</p></div></div>";
    exit;
}

// Soporte para el servidor interno de PHP (php -S)
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    if ($path !== '/' && file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
        return false;
    }
    if (!isset($_GET['url']) && $path !== '/') {
        $_GET['url'] = ltrim($path, '/');
    }
}

// Servir activos estáticos antes del enrutamiento de PHP para evitar que Vercel/dev
// entregue HTML en lugar de CSS/JSON/imagenes cuando la ruta cae en el router principal.
if (isset($_SERVER['REQUEST_URI'])) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    if (preg_match('#^/(manifest\.json|sw\.js|offline\.html|css|js|assets|images|icons|uploads|iconos_negocio)(?:/.*)?$#', $requestPath)) {
        $relativePath = ltrim($requestPath, '/');
        $localFile = __DIR__ . '/' . $relativePath;

        if (file_exists($localFile) && is_file($localFile)) {
            $realPublicDir = realpath(__DIR__);
            $realTarget = realpath($localFile);
            if ($realPublicDir !== false && $realTarget !== false && strpos($realTarget, $realPublicDir) === 0) {
                $mimeType = mime_content_type($localFile) ?: 'application/octet-stream';
                if (preg_match('/\.css$/i', $requestPath)) {
                    $mimeType = 'text/css; charset=utf-8';
                } elseif (preg_match('/\.js$/i', $requestPath)) {
                    $mimeType = 'application/javascript; charset=utf-8';
                } elseif (preg_match('/\.json$/i', $requestPath)) {
                    $mimeType = 'application/json; charset=utf-8';
                }

                header('Content-Type: ' . $mimeType);
                header('Cache-Control: public, max-age=31536000, immutable');
                readfile($localFile);
                exit;
            }
        }
    }
}

// Cargar configuración y core
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

// ==========================================
// MODO DIOS: MIDDLEWARE DE SEGURIDAD GLOBAL
// ==========================================
try {
    $db = Database::getInstance()->getConnection();
    // 1. Crear la tabla silenciosamente si no existe aún para evitar errores en el primer arranque
    $db->exec("CREATE TABLE IF NOT EXISTS banned_ips (id SERIAL PRIMARY KEY, ip_address VARCHAR(45) UNIQUE, reason TEXT, banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (id SERIAL PRIMARY KEY, ip_address VARCHAR(45), action VARCHAR(50), attempts INTEGER DEFAULT 0, last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE(ip_address, action))");
    
    // 2. Verificar IP actual
    $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    // Extraer la primera IP si hay múltiples (casos de proxies encadenados)
    if (strpos($clientIp, ',') !== false) {
        $clientIp = trim(explode(',', $clientIp)[0]);
    }

    $stmtBan = $db->prepare("SELECT reason FROM banned_ips WHERE ip_address = ?");
    $stmtBan->execute([$clientIp]);
    if ($banned = $stmtBan->fetch(PDO::FETCH_ASSOC)) {
        header('HTTP/1.1 403 Forbidden');
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Acceso Denegado</title>";
        echo "<style>body{background:#111827;color:#f87171;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;} .card{background:#1f2937;padding:3rem;border-radius:1rem;border:1px solid #7f1d1d;text-align:center;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);} h1{font-size:3rem;margin:0 0 1rem;} p{color:#9ca3af;font-size:1.1rem;} strong{color:#f87171;} svg{width:80px;height:80px;margin-bottom:1rem;color:#dc2626;}</style></head>";
        echo "<body><div class='card'>";
        echo "<svg fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'></path></svg>";
        echo "<h1>ACCESO BLOQUEADO</h1>";
        echo "<p>Tu dirección IP (<strong>{$clientIp}</strong>) ha sido restringida permanentemente por motivos de seguridad.</p>";
        if(!empty($banned['reason'])) {
            echo "<p style='margin-top:1.5rem;font-size:0.9rem;padding:1rem;background:#000;border-radius:0.5rem;'>Motivo: " . htmlspecialchars($banned['reason']) . "</p>";
        }
        echo "</div></body></html>";
        exit;
    }
} catch (Exception $e) {}
// ==========================================

// Fix para Vercel: Asegurar que $_GET tenga todos los query params reales de la URI
if (isset($_SERVER['REQUEST_URI'])) {
    $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    if ($query) {
        parse_str($query, $parsedQuery);
        $_GET = array_merge($_GET, $parsedQuery);
    }
}

// === MANEJADOR DE SESIONES EN BASE DE DATOS PARA VERCEL ===
class DbSessionHandler implements SessionHandlerInterface {
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string|false {
        $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['data'] : '';
    }
    public function write(string $id, string $data): bool {
        $sql = "INSERT INTO sessions (id, data, last_accessed) VALUES (?, ?, CURRENT_TIMESTAMP) ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_accessed = CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $data]);
    }
    public function destroy(string $id): bool {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }
    public function gc(int $max_lifetime): int|false {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE last_accessed < ?");
        $stmt->execute([date('Y-m-d H:i:s', time() - $max_lifetime)]);
        return 1;
    }
}

try {
    $db = Database::getInstance()->getConnection();
    // Auto-crear tabla de sesiones si no existe
    $db->exec("CREATE TABLE IF NOT EXISTS sessions (id VARCHAR(255) PRIMARY KEY, data TEXT, last_accessed TIMESTAMP)");
    session_set_save_handler(new DbSessionHandler($db), true);
} catch (Exception $e) {
    // Fallback a sesiones normales si falla la DB
}

if (session_status() === PHP_SESSION_NONE) {
    // [SESSION SECURITY] Configurar cookies de sesión con seguridad
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie (se cierra al cerrar navegador)
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
// Garantizar que la sesión se escriba en la BD antes de que se destruya el objeto PDO
register_shutdown_function('session_write_close');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Global CSRF Validation for POST requests
// Rutas públicas exentas: en Vercel serverless las sesiones no persisten entre
// instancias para visitantes anónimos, así que CSRF basado en sesión no funciona.
// Estas rutas ya están protegidas por rate limiting.
$requestUrl = $_GET['url'] ?? '';
$csrfExemptRoutes = [
    'tienda/registerClient',
    'tienda/checkout',
    'auth/check_unique',
];
$isExemptFromCsrf = false;
foreach ($csrfExemptRoutes as $route) {
    if (str_starts_with($requestUrl, $route)) {
        $isExemptFromCsrf = true;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isExemptFromCsrf) {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $isAjax = isset($_SERVER['HTTP_HX_REQUEST']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        if ($isAjax) {
            header('HTTP/1.1 403 Forbidden');
            header('HX-Trigger: {"csrfError": "Tu sesión ha expirado o el token de seguridad es inválido. Por favor recarga la página."}');
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido o expirado. Por favor recarga la página.']);
            exit;
        }
        
        $msg = "Tu sesión ha expirado o el token de seguridad es inválido. Por favor recarga la página e intenta de nuevo.";
        die("<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>Error de Seguridad</title><style>body{font-family:system-ui,sans-serif;background-color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;} .card{background:#fff;padding:2rem;border-radius:1rem;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);text-align:center;max-width:400px;} h2{color:#ef4444;margin-top:0;} button{background-color:#2563eb;color:#fff;border:none;padding:0.75rem 1.5rem;border-radius:0.5rem;font-weight:bold;cursor:pointer;margin-top:1rem;} button:hover{background-color:#1d4ed8;}</style></head><body><div class='card'><h2>⚠️ Error de Seguridad</h2><p>$msg</p><button onclick='window.location.href=\"/\"'>Ir al Inicio</button></div></body></html>");
    }
}

// === LOG VISITAS (Traffic Tracker) ===
if (!isset($_SESSION['tracked_visit_time']) || (time() - $_SESSION['tracked_visit_time'] > 3600)) {
    if (isset($db)) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $db->prepare("INSERT INTO site_visits (ip_address) VALUES (?)")->execute([$ip]);
            $_SESSION['tracked_visit_time'] = time();
        } catch(Exception $e){}
    }
}

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../core/Settings.php';
require_once __DIR__ . '/../core/BusinessProfileService.php';

$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';

if (empty($url)) {
    if (isset($_SESSION['user_id'])) {
        $url = 'dashboard';
    } else {
        $url = 'home';
    }
}

$urlParts = explode('/', $url);
$module = $urlParts[0];
$action = isset($urlParts[1]) ? explode('?', $urlParts[1])[0] : 'index';
$id     = isset($urlParts[2]) ? explode('?', $urlParts[2])[0] : null;

// ============================================================
// Mapa de Módulos y Configuración de Acceso
// ============================================================
$moduleMap = [
    // módulo        => [Controlador,              Roles permitidos,   Permiso requerido]
    'dashboard'      => ['DashboardController',    null,               null],
    'inventory'      => ['InventoryController',    null,               'inventory'],
    'sales'          => ['SalesController',        null,               'pos'],
    'suppliers'      => ['SuppliersController',    null,               'inventory'],
    'purchases'      => ['PurchasesController',    null,               'inventory'],
    'expenses'       => ['ExpensesController',     null,               'reports'],
    'reports'        => ['ReportsController',      null,               null],
    'cashbox'        => ['CashboxController',      null,               'pos'],
    'users'          => ['UsersController',        ['administrador'],  null],
    'settings'       => ['SettingsController',     null,               'settings'],
    'storefront'     => ['StorefrontController',   null,               'settings'],
    'clients'        => ['ClientsController',      null,               'clients'],
    'credits'        => ['CreditsController',      null,               'pos'],
    'suscription'    => ['SuscriptionController',  ['administrador'],  null],
    'superadmin'     => ['SuperadminController',   ['super_admin'],    null],
    'auth'           => ['AuthController',         'public',           null],
    'tienda'         => ['StorefrontController',   'public',           null],
    'qrmenu'         => ['QrMenuController',       'public',           null],
    'home'           => ['HomeController',         'public',           null],
    'restaurant'     => ['RestaurantController',   null,               'inventory'],
];

// Ruta especial: /tienda/{slug} → StorefrontController::show($slug)
if ($module === 'tienda' && !empty($action) && !in_array($action, ['index', 'registerClient', 'checkout', 'availability'])) {
    $id = $action;
    $action = 'show';
}

if (array_key_exists($module, $moduleMap)) {
    [$controllerName, $accessRoles, $requiredPermission] = $moduleMap[$module];

    // --- Middleware de Seguridad ---
    if ($accessRoles !== 'public') {
        Middleware::requireAuth();
        
        // BYPASS especial para SuperAdmin que está impersonando y necesita volver
        $isReturningSuperAdmin = ($module === 'superadmin' && $action === 'unimpersonate' && isset($_SESSION['superadmin_snapshot']));
        
        if (!$isReturningSuperAdmin) {
            if (is_array($accessRoles)) {
                Middleware::requireRole($accessRoles);
            }
            if ($requiredPermission) {
                Middleware::requirePermission($requiredPermission);
            }
            // Feature-based route guard: protect routes by business type capabilities
            $featureMap = [
                'restaurant' => 'recipes',
                'qrmenu' => 'qr_menu',
            ];
            if (isset($featureMap[$module]) && $_SESSION['role'] !== 'super_admin') {
                $category = $_SESSION['business_category'] ?? 'general';
                if (!BusinessProfileService::hasFeature($category, $featureMap[$module])) {
                    $msg = 'Este módulo no está disponible para tu tipo de negocio.';
                    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                        header('HTTP/1.1 403 Forbidden');
                        echo json_encode(['success' => false, 'message' => $msg]);
                        exit;
                    }
                    header('Location: ' . BASE_URL . 'dashboard');
                    exit;
                }
            }
        }
    }

    // Mapeo de alias de módulos a directorios físicos
    $moduleDirectoryMap = [
        'tienda' => 'storefront',
    ];
    $physicalModule = $moduleDirectoryMap[$module] ?? $module;

    $controllerPath = __DIR__ . '/../modules/' . $physicalModule . '/controllers/' . $controllerName . '.php';

    if (file_exists($controllerPath)) {
        require_once $controllerPath;
        $controller = new $controllerName();

        if (method_exists($controller, $action)) {
            if ($id !== null) {
                $controller->$action($id);
            } else {
                $controller->$action();
            }
        } else {
            http_response_code(404);
            include __DIR__ . '/../core/views/error.php';
        }
    } else {
        http_response_code(404);
        include __DIR__ . '/../core/views/error.php';
    }
} else {
    http_response_code(404);
    include __DIR__ . '/../core/views/error.php';
}
