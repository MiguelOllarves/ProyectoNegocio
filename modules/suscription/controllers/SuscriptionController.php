<?php
require_once __DIR__ . '/../models/Subscription.php';

class SuscriptionController extends Controller {
    public function __construct() {
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // Auto-migración silenciosa
        try {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $autoInc = $driver === 'pgsql' ? 'SERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
            
            // Tabla de planes
            $db->exec("CREATE TABLE IF NOT EXISTS plans (
                id INTEGER PRIMARY KEY,
                name VARCHAR(100),
                price DECIMAL(10,2),
                duration_days INTEGER DEFAULT 30,
                features_json TEXT
            )");
            
            // Tabla de pagos
            $db->exec("CREATE TABLE IF NOT EXISTS payments (
                id {$autoInc},
                tenant_id INTEGER,
                plan_id INTEGER,
                amount DECIMAL(10,2),
                payment_method VARCHAR(50),
                reference_number VARCHAR(100),
                proof_image TEXT,
                status VARCHAR(20) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Agregar columnas si no existen
            try { $db->exec("ALTER TABLE businesses ADD COLUMN plan_id INTEGER DEFAULT 1"); } catch (\Exception $e) {}
            try { $db->exec("ALTER TABLE businesses ADD COLUMN subscription_status VARCHAR(20) DEFAULT 'trial'"); } catch (\Exception $e) {}
            try { $db->exec("ALTER TABLE businesses ADD COLUMN trial_ends_at TIMESTAMP"); } catch (\Exception $e) {}
            
            // Insertar plan mensual $3 si no existe
            $checkPlans = $db->query("SELECT COUNT(*) FROM plans")->fetchColumn();
            if ($checkPlans == 0) {
                $db->exec("INSERT INTO plans (id, name, price, duration_days, features_json) VALUES 
                    (1, 'Plan Mensual', 3.00, 30, '{\"limit_users\": 999, \"limit_products\": 999999, \"custom_module\": true}')
                ");
            } else {
                // Asegurar que el plan $3 exista
                $checkPlan = $db->query("SELECT id FROM plans WHERE price = 3.00")->fetch();
                if (!$checkPlan) {
                    $db->exec("INSERT INTO plans (id, name, price, duration_days, features_json) VALUES 
                        (1, 'Plan Mensual', 3.00, 30, '{\"limit_users\": 999, \"limit_products\": 999999, \"custom_module\": true}')
                    ");
                }
                // Eliminar planes antiguos (solo quedamos con el $3)
                $db->exec("DELETE FROM plans WHERE price != 3.00");
            }
            
            // Asignar fecha de fin de trial por defecto si es null
            if ($driver === 'pgsql') {
                $db->exec("UPDATE businesses SET trial_ends_at = CURRENT_TIMESTAMP + INTERVAL '30 days' WHERE trial_ends_at IS NULL");
            } else {
                $db->exec("UPDATE businesses SET trial_ends_at = datetime('now', '+30 days') WHERE trial_ends_at = NULL");
            }
            
            // Actualizar suscripciones expiradas periódicamente
            Subscription::updateExpiredSubscriptions();
            
        } catch (\Exception $e) {
            error_log('[Suscription] init: ' . $e->getMessage());
        }
    }

    /**
     * Página principal de suscripción
     */
    public function index() {
        Middleware::requireAuth();

        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        $bizId = $_SESSION['business_id'];

        // Obtener estado de suscripción
        $subStatus = Subscription::checkStatus($bizId);
        
        // Obtener historial de pagos
        $stmtPayments = $db->prepare("SELECT p.*, pl.name as plan_name FROM payments p LEFT JOIN plans pl ON p.plan_id = pl.id WHERE p.tenant_id = ? ORDER BY p.created_at DESC LIMIT 10");
        $stmtPayments->execute([$bizId]);
        $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);
        
        // Verificar si puede pagar este mes
        $canPay = Subscription::canPay($bizId);
        
        // Calcular próximo día de pago
        $nextPaymentDate = new DateTime('first day of next month');

        $this->view('modules/suscription/views/index', [
            'status' => $subStatus['status'],
            'expires_at' => $subStatus['expires_at'],
            'days_remaining' => Subscription::getDaysRemaining($bizId),
            'can_pay' => $canPay,
            'next_payment_date' => $nextPaymentDate->format('d/m/Y'),
            'payments' => $payments,
            'price' => 3.00
        ]);
    }

    /**
     * Procesar pago de suscripción $3/mes
     */
    public function pay() {
        Middleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }
        
        Middleware::checkRateLimit('subscription_pay', 3, 60); // 3 intentos por hora
        
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        $bizId = $_SESSION['business_id'];

        $method = $_POST['payment_method'] ?? '';
        $reference = trim($_POST['reference_number'] ?? '');
        
        if (empty($method) || empty($reference)) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios del pago']);
            exit;
        }
        
        // Validar longitud de referencia
        if (strlen($reference) < 5 || strlen($reference) > 100) {
            echo json_encode(['success' => false, 'message' => 'La referencia debe tener entre 5 y 100 caracteres']);
            exit;
        }
        
        // Procesar imagen de prueba
        $proofBase64 = null;
        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['proof_image']['tmp_name'];
            $fileSize = $_FILES['proof_image']['size'];
            
            // Limitar tamaño a 5MB
            if ($fileSize > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'La imagen no debe superar 5MB']);
                exit;
            }
            
            $fileContent = file_get_contents($tmpName);
            $mimeType = mime_content_type($tmpName);
            if (strpos($mimeType, 'image/') === 0) {
                $proofBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($fileContent);
            }
        }
        
        // Registrar pago
        $result = Subscription::registerPayment($bizId, $method, $reference, $proofBase64);
        
        if ($result['success']) {
            // Notificar al super admin
            try {
                require_once __DIR__ . '/../../credits/models/Notification.php';
                $businessName = $_SESSION['business_name'] ?? ('Negocio ID: ' . $bizId);
                Notification::sendWithContext(
                    'suscription', 
                    'Nuevo Pago de Suscripción', 
                    "El negocio $businessName ha reportado un pago de $3.00 USD ($method, Ref: $reference).", 
                    'super_admin', 
                    'suscription_payment', 
                    null,
                    $bizId
                );
            } catch (\Exception $e) {
                error_log('[Suscription] notification: ' . $e->getMessage());
            }
        }
        
        Middleware::resetRateLimit('subscription_pay');
        
        echo json_encode($result);
        exit;
    }

    /**
     * API: Verificar estado de suscripción (para AJAX)
     */
    public function check() {
        Middleware::requireAuth();
        
        $bizId = $_SESSION['business_id'];
        $status = Subscription::checkStatus($bizId);
        
        header('Content-Type: application/json');
        echo json_encode($status);
        exit;
    }

    /**
     * API: Obtener días restantes
     */
    public function days() {
        Middleware::requireAuth();
        
        $bizId = $_SESSION['business_id'];
        $days = Subscription::getDaysRemaining($bizId);
        
        header('Content-Type: application/json');
        echo json_encode(['days' => $days]);
        exit;
    }
}
