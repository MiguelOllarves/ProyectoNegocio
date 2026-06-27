<?php
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
            header('Location: ' . BASE_URL . 'auth');
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

        $userRole = $_SESSION['role'] ?? 'cajero';

        if (!in_array($userRole, $roles)) {
            http_response_code(403);
            echo '<div style="text-align:center;margin-top:80px;font-family:sans-serif;">';
            echo '<h1 style="color:#dc2626;">403 - Acceso Denegado</h1>';
            echo '<p>No tienes permisos para acceder a esta sección.</p>';
            echo '<a href="' . BASE_URL . 'dashboard" style="color:#0ea5e9;">Volver al Panel</a>';
            echo '</div>';
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
}
