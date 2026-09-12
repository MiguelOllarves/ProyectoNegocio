<?php
require_once __DIR__ . '/../../../core/Model.php';

/**
 * Subscription - Modelo de suscripción $3/mes con expiración automática fin de mes.
 * 
 * Lógica:
 * - El usuario paga $3 USD para activar su suscripción del mes actual.
 * - La suscripción expira automáticamente el último día del mes a las 23:59:59.
 * - Al expirar, el sistema entra en "modo solo lectura" (solo puede ver datos).
 * - El día siguiente (1ro del mes), el usuario puede volver a pagar $3.
 * - Si paga el último día del mes, se le cuenta como pago del mes siguiente.
 */
class Subscription extends Model {
    protected $table = 'payments';
    protected $tenantColumn = 'tenant_id';

    /**
     * Verifica si el tenant tiene una suscripción activa para el mes actual.
     * @return array ['active' => bool, 'expires_at' => string|null, 'status' => string]
     */
    public static function checkStatus(int $tenantId): array {
        $db = Database::getInstance()->getConnection();
        
        // Buscar el último pago aprobado del tenant
        $stmt = $db->prepare("
            SELECT p.*, pl.duration_days 
            FROM payments p 
            JOIN plans pl ON p.plan_id = pl.id 
            WHERE p.tenant_id = ? AND p.status = 'approved'
            ORDER BY p.created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$tenantId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            // No tiene pagos aprobados - verificar trial
            $stmtBiz = $db->prepare("SELECT subscription_status, trial_ends_at FROM businesses WHERE id = ?");
            $stmtBiz->execute([$tenantId]);
            $biz = $stmtBiz->fetch(PDO::FETCH_ASSOC);
            
            if ($biz['subscription_status'] === 'trial' && strtotime($biz['trial_ends_at']) > time()) {
                return [
                    'active' => true,
                    'expires_at' => $biz['trial_ends_at'],
                    'status' => 'trial'
                ];
            }
            
            return [
                'active' => false,
                'expires_at' => null,
                'status' => 'expired'
            ];
        }
        
        // Calcular expiración basada en el último día del mes del pago
        $paymentDate = new DateTime($payment['created_at']);
        $expiresAt = self::getMonthEndDate($paymentDate);
        
        // Verificar si ya expiró
        if ($expiresAt < new DateTime()) {
            return [
                'active' => false,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'status' => 'expired'
            ];
        }
        
        return [
            'active' => true,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'status' => 'active'
        ];
    }
    
    /**
     * Obtiene la fecha de expiración del mes (último día a las 23:59:59).
     * @param DateTime $date Fecha de referencia (día del pago)
     * @return DateTime Último día del mes a las 23:59:59
     */
    public static function getMonthEndDate(DateTime $date): DateTime {
        $end = new DateTime($date->format('Y-m-t')); // 't' = último día del mes
        $end->setTime(23, 59, 59);
        return $end;
    }
    
    /**
     * Registra un pago y activa la suscripción del mes actual.
     * @param int $tenantId ID del negocio
     * @param string $method Método de pago (binance, bdv, etc.)
     * @param string $reference Número de referencia
     * @param string|null $proofBase64 Captura del pago en base64
     * @return array ['success' => bool, 'message' => string]
     */
    public static function registerPayment(int $tenantId, string $method, string $reference, ?string $proofBase64 = null): array {
        $db = Database::getInstance()->getConnection();
        
        // Verificar si ya tiene un pago aprobado para este mes
        $now = new DateTime();
        $monthStart = new DateTime($now->format('Y-m-01'));
        $monthEnd = self::getMonthEndDate($monthStart);
        
        $stmtCheck = $db->prepare("
            SELECT id FROM payments 
            WHERE tenant_id = ? AND status = 'approved' 
            AND created_at >= ? AND created_at <= ?
        ");
        $stmtCheck->execute([$tenantId, $monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')]);
        
        if ($stmtCheck->fetch()) {
            return [
                'success' => false,
                'message' => 'Ya tienes una suscripción activa para este mes. El próximo pago estará disponible a partir del próximo mes.'
            ];
        }
        
        // Obtener plan_id del plan mensual ($3)
        $stmtPlan = $db->prepare("SELECT id FROM plans WHERE price = 3.00 LIMIT 1");
        $stmtPlan->execute();
        $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) {
            // Crear plan si no existe
            $db->exec("INSERT INTO plans (id, name, price, duration_days, features_json) VALUES (1, 'Plan Mensual', 3.00, 30, '{\"limit_users\": 999, \"limit_products\": 999999}') ON CONFLICT (id) DO NOTHING");
            $stmtPlan = $db->prepare("SELECT id FROM plans WHERE price = 3.00 LIMIT 1");
            $stmtPlan->execute();
            $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);
        }
        
        try {
            $db->beginTransaction();
            
            // Insertar pago
            $stmtInsert = $db->prepare("
                INSERT INTO payments (tenant_id, plan_id, amount, payment_method, reference_number, proof_image, status) 
                VALUES (?, ?, 3.00, ?, ?, ?, 'approved')
            ");
            $stmtInsert->execute([$tenantId, $plan['id'], $method, $reference, $proofBase64]);
            
            // Actualizar estado del negocio
            $db->prepare("
                UPDATE businesses 
                SET subscription_status = 'active', 
                    plan_id = ?,
                    trial_ends_at = NULL 
                WHERE id = ?
            ")->execute([$plan['id'], $tenantId]);
            
            $db->commit();
            
            return [
                'success' => true,
                'message' => 'Suscripción activada exitosamente hasta fin de mes.',
                'expires_at' => $monthEnd->format('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('[Subscription] registerPayment: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar el pago. Por favor, intenta de nuevo.'
            ];
        }
    }
    
    /**
     * Actualiza el estado de suscripción de todos los tenants expirados.
     * Ejecutar periódicamente o al inicio de cada request.
     */
    public static function updateExpiredSubscriptions() {
        $db = Database::getInstance()->getConnection();
        
        // Buscar tenants con suscripción activa cuyo último pago ya expiró
        $stmt = $db->query("
            SELECT b.id, b.subscription_status, p.created_at as last_payment
            FROM businesses b
            JOIN payments p ON p.tenant_id = b.id AND p.status = 'approved'
            WHERE b.subscription_status = 'active'
            AND NOT EXISTS (
                SELECT 1 FROM payments p2 
                WHERE p2.tenant_id = b.id 
                AND p2.status = 'approved' 
                AND p2.created_at > p.created_at
            )
        ");
        
        $expired = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $biz) {
            $paymentDate = new DateTime($biz['last_payment']);
            $expiresAt = self::getMonthEndDate($paymentDate);
            
            if ($expiresAt < new DateTime()) {
                $db->prepare("UPDATE businesses SET subscription_status = 'expired' WHERE id = ?")->execute([$biz['id']]);
                $expired[] = $biz['id'];
            }
        }
        
        return $expired;
    }
    
    /**
     * Calcula los días restantes de suscripción.
     * @param int $tenantId
     * @return int Días restantes (0 si expiró)
     */
    public static function getDaysRemaining(int $tenantId): int {
        $status = self::checkStatus($tenantId);
        if (!$status['active']) return 0;
        
        $expires = new DateTime($status['expires_at']);
        $now = new DateTime();
        $diff = $now->diff($expires);
        
        return max(0, $diff->days);
    }
    
    /**
     * Verifica si el tenant puede hacer pagos (no tiene uno aprobado este mes).
     */
    public static function canPay(int $tenantId): bool {
        $db = Database::getInstance()->getConnection();
        
        $now = new DateTime();
        $monthStart = new DateTime($now->format('Y-m-01'));
        $monthEnd = self::getMonthEndDate($monthStart);
        
        $stmt = $db->prepare("
            SELECT id FROM payments 
            WHERE tenant_id = ? AND status = 'approved' 
            AND created_at >= ? AND created_at <= ?
        ");
        $stmt->execute([$tenantId, $monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')]);
        
        return !$stmt->fetch(); // Puede pagar si NO tiene pago aprobado este mes
    }
}
