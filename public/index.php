<?php
ob_start(); // Buffer all output to prevent stray warnings from corrupting JSON responses
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
            header('Cache-Control: max-age=86400');
            echo $imgData;
            exit;
        }
    } catch(Exception $e){}
    
    // Fallback static
    $file = __DIR__ . '/../iconos_negocio/logo1-t.png';
    if (file_exists($file)) {
        header('Content-Type: image/png');
        header('Cache-Control: max-age=86400');
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
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $db->exec("CREATE TABLE IF NOT EXISTS banned_ips (id INTEGER PRIMARY KEY AUTOINCREMENT, ip_address VARCHAR(45) UNIQUE, reason TEXT, banned_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (id INTEGER PRIMARY KEY AUTOINCREMENT, ip_address VARCHAR(45), action VARCHAR(50), attempts INTEGER DEFAULT 0, last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE(ip_address, action))");
    } else {
        $db->exec("CREATE TABLE IF NOT EXISTS banned_ips (id SERIAL PRIMARY KEY, ip_address VARCHAR(45) UNIQUE, reason TEXT, banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $db->exec("CREATE TABLE IF NOT EXISTS rate_limits (id SERIAL PRIMARY KEY, ip_address VARCHAR(45), action VARCHAR(50), attempts INTEGER DEFAULT 0, last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE(ip_address, action))");
    }
    
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
        if (is_array($accessRoles)) {
            Middleware::requireRole($accessRoles);
        }
        if ($requiredPermission) {
            Middleware::requirePermission($requiredPermission);
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
