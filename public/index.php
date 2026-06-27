<?php
session_start();

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
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Middleware.php';
require_once __DIR__ . '/../core/Settings.php';

$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'dashboard';
$urlParts = explode('/', $url);

$module = $urlParts[0];
$action = isset($urlParts[1]) ? $urlParts[1] : 'index';
$id     = isset($urlParts[2]) ? $urlParts[2] : null;

// ============================================================
// Mapa de Módulos y Configuración de Acceso
// ============================================================
$moduleMap = [
    // módulo        => [Controlador,              Roles permitidos (null = todos los autenticados)]
    'dashboard'      => ['DashboardController',    null],
    'inventory'      => ['InventoryController',    null],
    'sales'          => ['SalesController',        null],
    'suppliers'      => ['SuppliersController',    ['admin']],
    'purchases'      => ['PurchasesController',    ['admin']],
    'expenses'       => ['ExpensesController',     ['admin']],
    'reports'        => ['ReportsController',      ['admin']],
    'cashbox'        => ['CashboxController',      null],
    'users'          => ['UsersController',        ['admin']],
    'settings'       => ['SettingsController',     ['admin']],
    'auth'           => ['AuthController',         'public'], // No requiere auth
];

if (array_key_exists($module, $moduleMap)) {
    [$controllerName, $accessRoles] = $moduleMap[$module];

    // --- Middleware de Seguridad ---
    if ($accessRoles !== 'public') {
        Middleware::requireAuth();
        if (is_array($accessRoles)) {
            Middleware::requireRole($accessRoles);
        }
    }

    $controllerPath = __DIR__ . '/../modules/' . $module . '/controllers/' . $controllerName . '.php';

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
            echo "404 - Acción '$action' no encontrada.";
        }
    } else {
        http_response_code(404);
        echo "404 - Controlador no encontrado ($controllerName).";
    }
} else {
    http_response_code(404);
    echo "404 - Módulo '$module' no encontrado.";
}
