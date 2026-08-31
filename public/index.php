<?php
ob_start(); // Buffer all output to prevent stray warnings from corrupting JSON responses

// ==========================================
// MANEJADOR DE ERRORES VISUALES
// ==========================================
function renderVisualError($title, $message, $file = '', $line = '') {
    if (ob_get_level()) ob_clean();
    http_response_code(500);
    $html = "<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>Error del Sistema</title>";
    $html .= "<style>body{background:#f8fafc;color:#1e293b;font-family:system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1rem;} .card{background:#fff;padding:2rem;border-radius:1rem;border-top:4px solid #ef4444;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);max-width:650px;width:100%;} h1{color:#dc2626;margin:0 0 1rem;font-size:1.5rem;display:flex;align-items:center;gap:0.5rem;} p{margin:0 0 1rem;line-height:1.5;} .details{background:#1e293b;padding:1.5rem;border-radius:0.5rem;font-family:monospace;font-size:0.875rem;overflow-x:auto;color:#e2e8f0;margin-top:1rem;word-wrap:break-word;} .file{color:#38bdf8;font-weight:bold;} .btn{display:inline-block;background:#ef4444;color:#fff;text-decoration:none;padding:0.75rem 1.5rem;border-radius:0.5rem;font-weight:bold;transition:all 0.2s;border:none;cursor:pointer;} .btn:hover{background:#dc2626;box-shadow:0 4px 6px -1px rgba(239,68,68,0.3);} .header-err{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:1rem;margin-bottom:1rem;}</style></head>";
    $html .= "<body><div class='card'>";
    $html .= "<div class='header-err'><h1><svg style='width:28px;height:28px;' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'></path></svg> $title</h1>";
    $html .= "<span style='background:#fee2e2;color:#991b1b;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.75rem;font-weight:bold;'>HTTP 500</span></div>";
    
    // Simplificar el mensaje para usuarios finales, pero manteniendo el detalle técnico
    $html .= "<p>El sistema ha encontrado un problema procesando tu solicitud. Nuestro equipo técnico ha sido notificado.</p>";
    
    if ($file && $line) {
        $html .= "<div class='details'>";
        $html .= "<div style='color:#94a3b8;margin-bottom:0.5rem;'>// Detalles técnicos del error:</div>";
        $html .= "<div style='color:#f87171;margin-bottom:0.5rem;'>" . htmlspecialchars($message) . "</div>";
        $html .= "En archivo: <span class='file'>" . htmlspecialchars($file) . "</span><br>Línea: <span style='color:#fbbf24;'>" . (int)$line . "</span>";
        $html .= "</div>";
    }
    $html .= "<div style='margin-top:1.5rem;text-align:right;'><a href='javascript:history.back()' class='btn' style='background:#64748b;margin-right:0.5rem;'>Volver Atrás</a><a href='/' class='btn'>Ir al Inicio</a></div>";
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
if (isset($_GET['serve_logo'])) {
    require_once __DIR__ . '/../config/Database.php';
    session_start();
    $tenant_id = $_SESSION['business_id'] ?? 1;
    if (isset($_GET['tenant'])) $tenant_id = (int)$_GET['tenant'];

    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT logo_base64 FROM businesses WHERE id = ?");
        $stmt->execute([$tenant_id]);
        $base64 = $stmt->fetchColumn();

        if ($base64) {
            // base64 looks like "data:image/png;base64,iVBORw0KGgo..."
            list($type, $data) = explode(';', $base64);
            list(, $data)      = explode(',', $data);
            $imgData = base64_decode($data);
            $mime = str_replace('data:', '', $type);
            header("Content-Type: $mime");
            header('Cache-Control: public, s-maxage=86400, max-age=86400');
            echo $imgData;
            exit;
        }
    } catch(Exception $e){}
    
    // Fallback static
    $file = __DIR__ . '/../iconos_negocio/logo1-t.png';
    if (file_exists($file)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, s-maxage=86400, max-age=86400');
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
        die("<!DOCTYPE html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><title>Error de Seguridad</title><style>body{font-family:system-ui,sans-serif;background-color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;} .card{background:#fff;padding:2rem;border-radius:1rem;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);text-align:center;max-width:400px;} h2{color:#ef4444;margin-top:0;} button{background-color:#10b981;color:#fff;border:none;padding:0.75rem 1.5rem;border-radius:0.5rem;font-weight:bold;cursor:pointer;margin-top:1rem;} button:hover{background-color:#059669;}</style></head><body><div class='card'><h2>⚠️ Error de Seguridad</h2><p>$msg</p><button onclick='window.location.reload()'>Recargar Página</button></div></body></html>");
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
if ($module === 'tienda' && !empty($action) && !in_array($action, ['index', 'registerClient', 'checkout'])) {
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
