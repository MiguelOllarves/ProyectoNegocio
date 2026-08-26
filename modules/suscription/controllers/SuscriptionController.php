<?php
class SuscriptionController extends Controller {
    public function __construct() {
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // Auto-migración silenciosa (SQLite / PostgreSQL)
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS plans (
                id INTEGER PRIMARY KEY,
                name VARCHAR(100),
                price DECIMAL(10,2),
                duration_days INTEGER DEFAULT 30,
                features_json TEXT
            )");
            
            // Agregar columna duration_days si no existe (migración suave)
            try { $db->exec("ALTER TABLE plans ADD COLUMN duration_days INTEGER DEFAULT 30"); } catch (\Exception $e) {}
            
            // Si la tabla está vacía, insertar los dos planes
            $checkPlans = $db->query("SELECT COUNT(*) FROM plans")->fetchColumn();
            if ($checkPlans == 0) {
                $db->exec("INSERT INTO plans (id, name, price, duration_days, features_json) VALUES 
                    (1, 'Plan Básico', 10.00, 30, '{\"limit_users\": 2, \"limit_products\": 100, \"custom_module\": true}'),
                    (2, 'Plan Anual', 199.00, 365, '{\"limit_users\": 4, \"limit_products\": 200, \"custom_module\": true}')
                ");
            } else {
                // Asegurar que el plan anual exista
                $checkAnual = $db->query("SELECT id FROM plans WHERE id = 2")->fetch();
                if (!$checkAnual) {
                    $db->exec("INSERT INTO plans (id, name, price, duration_days, features_json) VALUES (2, 'Plan Anual', 199.00, 365, '{\"limit_users\": 4, \"limit_products\": 200, \"custom_module\": true}')");
                }
                // Actualizar planes existentes si tienen datos incompletos
                $db->exec("UPDATE plans SET name = 'Plan Básico', price = 10.00, duration_days = 30, features_json = '{\"limit_users\": 2, \"limit_products\": 100, \"custom_module\": true}' WHERE id = 1 AND (price = 0 OR duration_days IS NULL)");
                $db->exec("UPDATE plans SET name = 'Plan Anual', price = 199.00, duration_days = 365, features_json = '{\"limit_users\": 4, \"limit_products\": 200, \"custom_module\": true}' WHERE id = 2 AND (price = 40 OR duration_days IS NULL)");
                // Eliminar planes Anuales duplicados (por ejemplo ID 3 de migraciones anteriores)
                $db->exec("DELETE FROM plans WHERE name = 'Plan Anual' AND id != 2");
            }
        } catch (\Exception $e) {}

        try {
            $db->exec("CREATE TABLE IF NOT EXISTS payments (
                id " . ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 'SERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT') . ",
                tenant_id INTEGER,
                plan_id INTEGER,
                amount DECIMAL(10,2),
                payment_method VARCHAR(50),
                reference_number VARCHAR(100),
                proof_image TEXT,
                status VARCHAR(20) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        } catch (\Exception $e) {}

        try { $db->exec("ALTER TABLE businesses ADD COLUMN plan_id INTEGER DEFAULT 1"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE businesses ADD COLUMN subscription_status VARCHAR(20) DEFAULT 'trial'"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE businesses ADD COLUMN trial_ends_at TIMESTAMP"); } catch (\Exception $e) {}
        
        // Asignar fecha de fin de trial por defecto si es null
        try {
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                $db->exec("UPDATE businesses SET trial_ends_at = CURRENT_TIMESTAMP + INTERVAL '30 days' WHERE trial_ends_at IS NULL");
            } else {
                $db->exec("UPDATE businesses SET trial_ends_at = datetime('now', '+30 days') WHERE trial_ends_at IS NULL");
            }
        } catch (\Exception $e) {}
    }

    public function index() {
        Middleware::requireAuth();

        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        $bizId = $_SESSION['business_id'];

        $stmtPlans = $db->query("SELECT * FROM plans ORDER BY price ASC");
        $plans = $stmtPlans->fetchAll(PDO::FETCH_ASSOC);

        $stmtBiz = $db->prepare("SELECT subscription_status, trial_ends_at, plan_id FROM businesses WHERE id = ?");
        $stmtBiz->execute([$bizId]);
        $biz = $stmtBiz->fetch(PDO::FETCH_ASSOC);

        $stmtPayments = $db->prepare("SELECT * FROM payments WHERE tenant_id = ? ORDER BY created_at DESC");
        $stmtPayments->execute([$bizId]);
        $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

        $this->view('modules/suscription/views/index', [
            'plans' => $plans,
            'status' => $biz['subscription_status'],
            'trial_ends' => $biz['trial_ends_at'],
            'current_plan' => $biz['plan_id'],
            'payments' => $payments
        ]);
    }

    public function pay() {
        Middleware::requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $bizId = $_SESSION['business_id'];

            $planId = $_POST['plan_id'] ?? 0;
            $method = $_POST['payment_method'] ?? '';
            $reference = $_POST['reference_number'] ?? '';
            
            // Get Plan Price
            $stmt = $db->prepare("SELECT price FROM plans WHERE id = ?");
            $stmt->execute([$planId]);
            $plan = $stmt->fetch();
            
            if (!$plan || empty($method) || empty($reference)) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios del pago']);
                exit;
            }
            
            $proofBase64 = null;
            if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['proof_image']['tmp_name'];
                $fileContent = file_get_contents($tmpName);
                $mimeType = mime_content_type($tmpName);
                if (strpos($mimeType, 'image/') === 0) {
                    $proofBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($fileContent);
                }
            }

            try {
                $db->beginTransaction();
                $stmtInsert = $db->prepare("INSERT INTO payments (tenant_id, plan_id, amount, payment_method, reference_number, proof_image, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmtInsert->execute([$bizId, $planId, $plan['price'], $method, $reference, $proofBase64]);
                
                $newPaymentId = $db->lastInsertId();
                $db->commit();
                
                // NOTIFY SUPER ADMIN
                require_once __DIR__ . '/../../credits/models/Notification.php';
                try {
                    $businessName = $_SESSION['business_name'] ?? ('Negocio ID: ' . $bizId);
                    Notification::send('suscription', 'Nuevo Pago de Suscripción', "El negocio $businessName ha reportado un pago por $" . number_format($plan['price'], 2) . " ($method, Ref: $reference).", 'super_admin', 'suscription_payment', $newPaymentId);
                } catch (\Exception $e) {}
                
                echo json_encode(['success' => true, 'message' => 'Reporte enviado con éxito. Esperando validación del administrador.']);
            } catch(Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error al registrar pago: '.$e->getMessage()]);
            }
            exit;
        }
    }
}
