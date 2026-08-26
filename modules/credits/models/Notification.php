<?php
require_once __DIR__ . '/../../../core/Model.php';

class Notification extends Model {
    protected $table = 'notifications';
    protected $tenantColumn = 'tenant_id';

    /**
     * Obtiene notificaciones no leídas filtradas por rol y/o usuario.
     * Incluye TODAS las alertas de crédito + notificaciones de pagos.
     */
    public function getUnread($role = null, $userId = null) {
        $sql = "SELECT * FROM notifications WHERE is_read = FALSE";
        $params = [];

        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $sql .= " AND tenant_id = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }

        // Filtrar: notificaciones globales O para mi rol O para mi usuario
        $sql .= " AND (target_role IS NULL OR target_role = :role OR target_user_id = :user_id)";
        $params['role'] = $role;
        $params['user_id'] = $userId;

        $sql .= " ORDER BY created_at DESC LIMIT 50";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Cuenta las notificaciones no leídas (para el badge de la campana).
     */
    public function countUnread($role = null, $userId = null) {
        $sql = "SELECT COUNT(*) FROM notifications WHERE is_read = FALSE";
        $params = [];

        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $sql .= " AND tenant_id = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }

        $sql .= " AND (target_role IS NULL OR target_role = :role OR target_user_id = :user_id)";
        $params['role'] = $role;
        $params['user_id'] = $userId;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca una notificación como leída.
     */
    public function markAsRead($id) {
        return $this->update($id, ['is_read' => 'true']);
    }

    /**
     * Marca todas las notificaciones del usuario como leídas.
     */
    public function markAllAsRead($role = null, $userId = null) {
        $sql = "UPDATE notifications SET is_read = TRUE WHERE is_read = FALSE";
        $params = [];

        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $sql .= " AND tenant_id = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }

        $sql .= " AND (target_role IS NULL OR target_role = :role OR target_user_id = :user_id)";
        $params['role'] = $role;
        $params['user_id'] = $userId;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Crea una notificación de forma rápida (helper).
     */
    public static function send($type, $title, $message, $targetRole = null, $refType = null, $refId = null, $targetUserId = null) {
        $notification = new self();
        $inserted = $notification->create([
            'type'           => $type,
            'title'          => $title,
            'message'        => $message,
            'target_role'    => $targetRole,
            'target_user_id' => $targetUserId,
            'reference_type' => $refType,
            'reference_id'   => $refId,
        ]);
        
        // PUSH NOTIFICATION BACKEND (WEB PUSH API)
        // Intentaremos mandar la notificación a TODOS los endpoints válidos
        try {
            $autoload = __DIR__ . '/../../../vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
                if (class_exists('\Minishlink\WebPush\WebPush')) {
                    $db = \Database::getInstance()->getConnection();
                    
                    $tenantId = $_SESSION['business_id'] ?? null;
                    
                    if ($tenantId) {
                        // Scope subscriptions to the active tenant, but ALWAYS include super_admin
                        $sql = "SELECT ps.* FROM push_subscriptions ps 
                                LEFT JOIN users u ON ps.user_id = u.id 
                                WHERE (ps.user_id = ? OR ps.role = ?) 
                                AND (u.tenant_id = ? OR ps.user_id IS NULL OR u.role = 'super_admin')";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$targetUserId, $targetRole, $tenantId]);
                    } else {
                        // Fallback (e.g. for super_admin general alerts)
                        $sql = "SELECT * FROM push_subscriptions WHERE user_id = ? OR role = ?";
                        $stmt = $db->prepare($sql);
                        $stmt->execute([$targetUserId, $targetRole]);
                    }
                    
                    $subs = $stmt->fetchAll();
                    
                    if (count($subs) > 0) {
                        require_once __DIR__ . '/../../../config/config.php';
                        $auth = [
                            'VAPID' => [
                                'subject' => 'mailto:admin@tuinventario.app',
                                'publicKey' => VAPID_PUBLIC_KEY,
                                'privateKey' => VAPID_PRIVATE_KEY
                            ]
                        ];
                        
                        $webPush = new \Minishlink\WebPush\WebPush($auth);
                        $payload = json_encode([
                            'title' => $title,
                            'body' => $message,
                            'icon' => '/icon-192x192.png',
                            'badge' => '/badge-72x72.png',
                            'url' => '/' 
                        ]);

                        foreach ($subs as $sub) {
                            $subscription = \Minishlink\WebPush\Subscription::create([
                                'endpoint' => $sub['endpoint'],
                                'publicKey' => $sub['p256dh'],
                                'authToken' => $sub['auth'],
                            ]);
                            $webPush->sendOneNotification($subscription, $payload);
                        }

                        // Check reports for expired subscriptions and clean them up
                        foreach ($webPush->flush() as $report) {
                            $endpoint = $report->getRequest()->getUri()->__toString();
                            if (!$report->isSuccess()) {
                                if ($report->isSubscriptionExpired()) {
                                    $stmtDelete = $db->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
                                    $stmtDelete->execute([$endpoint]);
                                } else {
                                    error_log("[WebPush Error] Endpoint: {$endpoint}, Error: {$report->getReason()}");
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("[WebPush Error] " . $e->getMessage());
        }

        return $inserted;
    }
}
