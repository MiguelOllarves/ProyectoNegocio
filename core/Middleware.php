<?php
require_once __DIR__ . '/FlashMessage.php';

/**
 * Middleware - Sistema de control de acceso y sesión.
 * 
 * Uso en el Router:
 *   Middleware::requireAuth();           // Requiere login
 *   Middleware::requireRole('admin');    // Requiere rol específico
 */
class Middleware {

    /**
     * Valida que el usuario tenga una sesión activa.
     * Si no, redirige al login.
     */
    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                header('HX-Redirect: ' . BASE_URL . 'auth');
                exit;
            }
            header('Location: ' . BASE_URL . 'auth');
            exit;
        }



        // Anti-Caché estricto para evitar retroceso (Back Button)
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Control de bloqueo progresivo (Modo Solo Lectura)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::checkSubscriptionWriteAccess();
        }
    }

    /**
     * Valida que el tenant tenga una suscripción activa o en periodo de prueba vigente
     * antes de permitirle ejecutar modificaciones en el sistema (POST).
     */
    private static function checkSubscriptionWriteAccess() {
        $url = rtrim($_GET['url'] ?? '', '/');
        // Permitir acciones de Auth (Login/Logout) y Módulo Suscripción
        if (strpos($url, 'auth/') === 0 || strpos($url, 'suscription/') === 0) {
            return;
        }
        
        // El SuperAdmin puede hacer todo independientemente del estado del tenant
        if (($_SESSION['role'] ?? '') === 'super_admin') {
            return;
        }

        require_once __DIR__ . '/../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $bizId = $_SESSION['business_id'] ?? null;
        if (!$bizId) return;

        $stmt = $db->prepare("SELECT subscription_status, trial_ends_at FROM businesses WHERE id = ?");
        $stmt->execute([$bizId]);
        $biz = $stmt->fetch(PDO::FETCH_ASSOC);

        $isExpired = false;
        if ($biz['subscription_status'] === 'expired') {
            $isExpired = true;
        } else if ($biz['subscription_status'] === 'trial') {
            if (strtotime($biz['trial_ends_at']) < time()) {
                $isExpired = true;
                // Auto-actualizar a expired para evitar cálculos futuros
                $db->prepare("UPDATE businesses SET subscription_status = 'expired' WHERE id = ?")->execute([$bizId]);
            }
        }

        if ($isExpired) {
            $msg = 'Suscripción o Periodo de Prueba Finalizado (Modo Solo Lectura).';
            $isAjax = isset($_SERVER['HTTP_HX_REQUEST']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
            
            if ($isAjax) {
                header('HTTP/1.1 403 Forbidden');
                // HTMX hook for a sweetalert 
                header('HX-Trigger: {"showAlert": "'.$msg.'"}');
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
            
            FlashMessage::set('error', 'Suscripción o Periodo de Prueba Finalizado (Modo Solo Lectura).');
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }
    }

    /**
     * Valida que el usuario tenga un rol específico.
     * Llama internamente a requireAuth().
     * @param string|array $roles Un rol o array de roles permitidos.
     */
    public static function requireRole($roles) {
        self::requireAuth();

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {
            FlashMessage::set('error', 'Acceso denegado: No tienes el rol necesario para esta sección.');
            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                header('HX-Redirect: ' . BASE_URL . 'dashboard');
                exit;
            }
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }
    }

    /**
     * Valida que el usuario tenga un permiso específico en su JSON de permisos.
     * Si es 'administrador' o 'super_admin', se le permite acceso total.
     */
    public static function requirePermission($perm) {
        self::requireAuth();

        $role = $_SESSION['role'] ?? '';
        if ($role === 'administrador' || $role === 'super_admin') {
            return;
        }

        $perms = $_SESSION['permissions'] ?? [];
        if (!in_array($perm, $perms)) {
            $isAjax = isset($_SERVER['HTTP_HX_REQUEST']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
            if ($isAjax) {
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(['success' => false, 'message' => 'Acceso denegado: No tienes permiso para esta acción.']);
                exit;
            }
            FlashMessage::set('error', 'Acceso denegado: No tienes permiso para esta acción.');
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }
    }

    /**
     * Devuelve true si el usuario actual tiene el rol dado.
     */
    public static function hasRole($role) {
        return ($_SESSION['role'] ?? '') === $role;
    }

    /**
     * Devuelve el ID del usuario actual o null.
     */
    public static function userId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Rate Limiting: Bloquea intentos excesivos de una acción específica por IP.
     */
    public static function checkRateLimit($action, $maxAttempts = 5, $lockoutMinutes = 15) {
        require_once __DIR__ . '/../config/Database.php';
        $db = Database::getInstance()->getConnection();
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $stmt = $db->prepare("SELECT attempts, last_attempt FROM rate_limits WHERE ip_address = ? AND action = ?");
        $stmt->execute([$ip, $action]);
        $row = $stmt->fetch();
        
        $now = time();
        if ($row) {
            $last = strtotime($row['last_attempt']);
            if ($now - $last > $lockoutMinutes * 60) {
                // Reset
                $db->prepare("UPDATE rate_limits SET attempts = 1, last_attempt = CURRENT_TIMESTAMP WHERE ip_address = ? AND action = ?")->execute([$ip, $action]);
                return true;
            } else {
                if ($row['attempts'] >= $maxAttempts) {
                    return false; // Blocked
                }
                $db->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP WHERE ip_address = ? AND action = ?")->execute([$ip, $action]);
                return true;
            }
        } else {
            $db->prepare("INSERT INTO rate_limits (ip_address, action, attempts) VALUES (?, ?, 1)")->execute([$ip, $action]);
            return true;
        }
    }

    /**
     * Rate Limiting: Reinicia los intentos tras un éxito.
     */
    public static function resetRateLimit($action) {
        require_once __DIR__ . '/../config/Database.php';
        $db = Database::getInstance()->getConnection();
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $db->prepare("DELETE FROM rate_limits WHERE ip_address = ? AND action = ?")->execute([$ip, $action]);
    }

    /**
     * Valida la complejidad de una contraseña.
     * Retorna true si es válida, o un string con el error si no lo es.
     */
    public static function validatePasswordComplexity($password) {
        if (strlen($password) < 8) {
            return "La contraseña debe tener al menos 8 caracteres.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return "La contraseña debe contener al menos una letra mayúscula.";
        }
        if (!preg_match('/[a-z]/', $password)) {
            return "La contraseña debe contener al menos una letra minúscula.";
        }
        if (!preg_match('/[0-9]/', $password)) {
            return "La contraseña debe contener al menos un número.";
        }
        return true;
    }
}

