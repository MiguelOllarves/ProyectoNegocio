<?php
class SettingsController extends Controller {
    
    private function getDb() {
        return Database::getInstance()->getConnection();
    }

    public function index() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }

        $db = $this->getDb();
        
        // Obtener configuraciones como key => value
        $settingsQuery = $db->query("SELECT key, value FROM settings");
        $settings = $settingsQuery->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Obtener métodos de pago
        $paymentsQuery = $db->query("SELECT * FROM payment_methods ORDER BY id ASC");
        $paymentMethods = $paymentsQuery->fetchAll(PDO::FETCH_ASSOC);

        $this->view('modules/settings/views/index', [
            'settings' => $settings,
            'paymentMethods' => $paymentMethods
        ]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $db = $this->getDb();
        
        // Usar Settings::set() que es robusto y no depende de columna 'category'
        require_once __DIR__ . '/../../../core/Settings.php';

        $allowedKeys = [
            'bcv_rate', 'parallel_rate', 'cop_rate',
            'tax_iva', 'tax_igtf',
            'calc_method', 'iva_method',
            'business_name'
        ];

        foreach ($_POST as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                Settings::set($key, trim($value));
            }
        }

        $this->jsonResponse(['success' => true, 'message' => 'Configuración guardada']);
    }

    public function addPaymentMethod() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $db = $this->getDb();
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) $data = $_POST;
        
        $name     = trim($data['name'] ?? '');
        $code     = trim($data['code'] ?? '');
        $currency = $data['currency'] ?? 'VES';
        $igtf     = !empty($data['applies_igtf']) ? 1 : 0;

        if (empty($name) || empty($code)) {
            $this->jsonResponse(['success' => false, 'message' => 'Nombre y código son requeridos'], 400);
            return;
        }

        try {
            $stmt = $db->prepare("INSERT INTO payment_methods (name, code, currency, applies_igtf, is_active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$name, $code, $currency, $igtf]);
            $this->jsonResponse(['success' => true, 'message' => 'Método de pago agregado']);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'El código ya existe o error: ' . $e->getMessage()], 500);
        }
    }

    public function changePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (strlen($password) < 6) {
            $this->jsonResponse(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'], 400);
            return;
        }
        if ($password !== $confirm) {
            $this->jsonResponse(['success' => false, 'message' => 'Las contraseñas no coinciden'], 400);
            return;
        }

        $db = $this->getDb();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['user_id']]);

        $this->jsonResponse(['success' => true, 'message' => 'Contraseña actualizada']);
    }
}
