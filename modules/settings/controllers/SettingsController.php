<?php
class SettingsController extends Controller {
    
    private function getDb() {
        return Database::getInstance()->getConnection();
    }

    public function index() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrador') {
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

        // Obtener infos del negocio sin cargar los Base64 (para evitar 'FUNCTION_RESPONSE_PAYLOAD_TOO_LARGE')
        $stmtBiz = $db->prepare("SELECT ticket_header, ticket_footer, menu_file_type, slug, 
            (logo_base64 IS NOT NULL AND logo_base64 != '') as has_logo,
            (menu_file_base64 IS NOT NULL AND menu_file_base64 != '') as has_menu
        FROM businesses WHERE id = ?");
        $stmtBiz->execute([$_SESSION['business_id']]);
        $bizData = $stmtBiz->fetch(PDO::FETCH_ASSOC);

        $this->view('modules/settings/views/index', [
            'settings' => $settings,
            'paymentMethods' => $paymentMethods,
            'bizData' => $bizData
        ]);
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $db = $this->getDb();
        
        // Usar Settings::set() que es robusto y no depende de columna 'category'
        require_once __DIR__ . '/../../../core/Settings.php';

        $allowedKeys = [
            'bcv_rate', 'bcv_auto_update', 'parallel_rate', 'cop_rate',
            'tax_iva', 'tax_igtf',
            'calc_method', 'iva_method',
            'business_name'
        ];

        foreach ($_POST as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                Settings::set($key, trim($value));
            }
        }

        // Manejar logo y tickets del negocio
        $business_id = $_SESSION['business_id'];
        $updates = [];
        $params = [];

        if (isset($_POST['ticket_header'])) {
            $updates[] = "ticket_header = ?";
            $params[] = $_POST['ticket_header'];
        }
        if (isset($_POST['ticket_footer'])) {
            $updates[] = "ticket_footer = ?";
            $params[] = $_POST['ticket_footer'];
        }
        if (!empty($_POST['logo_base64'])) {
            $base64 = $_POST['logo_base64'];
            if (strlen($base64) > 4000000) {
                $this->jsonResponse(['success' => false, 'message' => 'La imagen de logo es demasiado grande (Máx 3MB)'], 400);
                return;
            }
            if (!str_starts_with($base64, 'data:image/jpeg') && !str_starts_with($base64, 'data:image/png') && !str_starts_with($base64, 'data:image/webp')) {
                $this->jsonResponse(['success' => false, 'message' => 'Formato de imagen inválido (solo JPG, PNG, WEBP)'], 400);
                return;
            }
            $updates[] = "logo_base64 = ?";
            $params[] = $base64;
        }
        
        if (isset($_POST['menu_file_base64'])) {
            $base64 = $_POST['menu_file_base64'];
            if (!empty($base64)) {
                if (strlen($base64) > 8000000) { // ~6MB
                    $this->jsonResponse(['success' => false, 'message' => 'El archivo del menú es demasiado grande (Máx 6MB)'], 400);
                    return;
                }
                $parts = explode(';', $base64);
                $mime = count($parts) > 1 ? str_replace('data:', '', $parts[0]) : 'application/pdf';
                
                $updates[] = "menu_file_base64 = ?";
                $params[] = $base64;
                $updates[] = "menu_file_type = ?";
                $params[] = $mime;
            } else {
                $updates[] = "menu_file_base64 = NULL";
                $updates[] = "menu_file_type = NULL";
            }
        }

        if (!empty($updates)) {
            $params[] = $business_id;
            $sql = "UPDATE businesses SET " . implode(", ", $updates) . " WHERE id = ?";
            $db->prepare($sql)->execute($params);
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

    public function factoryReset() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrador') {
            $this->jsonResponse(['success' => false, 'message' => 'Acceso denegado'], 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['confirm'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Confirmación requerida'], 400);
            return;
        }

        $db = $this->getDb();
        $tenant_id = $_SESSION['business_id'];

        try {
            $db->beginTransaction();

            // Store Orders
            $stmt = $db->prepare("DELETE FROM store_orders WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);

            // Notifications
            $stmt = $db->prepare("DELETE FROM notifications WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);

            // Credit Payments
            // We delete payments where credit belongs to tenant
            $stmt = $db->prepare("DELETE FROM credit_payments WHERE credit_id IN (SELECT id FROM credits WHERE tenant_id = ?)");
            $stmt->execute([$tenant_id]);

            // Credits
            $stmt = $db->prepare("DELETE FROM credits WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);

            // Arqueo Caja limit to user who belongs to tenant
            $stmt = $db->prepare("DELETE FROM arqueo_caja WHERE user_id IN (SELECT id FROM users WHERE business_id = ?)");
            $stmt->execute([$tenant_id]);

            // Kardex
            $stmt = $db->prepare("DELETE FROM kardex WHERE product_id IN (SELECT id FROM products WHERE tenant_id = ?)");
            $stmt->execute([$tenant_id]);

            // Expenses
            $stmt = $db->prepare("DELETE FROM expenses WHERE user_id IN (SELECT id FROM users WHERE business_id = ?)");
            $stmt->execute([$tenant_id]);

            // Purchase Items
            $stmt = $db->prepare("DELETE FROM purchase_items WHERE purchase_id IN (SELECT id FROM purchases WHERE user_id IN (SELECT id FROM users WHERE business_id = ?))");
            $stmt->execute([$tenant_id]);

            // Purchases
            $stmt = $db->prepare("DELETE FROM purchases WHERE user_id IN (SELECT id FROM users WHERE business_id = ?)");
            $stmt->execute([$tenant_id]);

            // Sale Items
            $stmt = $db->prepare("DELETE FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE user_id IN (SELECT id FROM users WHERE business_id = ?))");
            $stmt->execute([$tenant_id]);

            // Sales
            $stmt = $db->prepare("DELETE FROM sales WHERE user_id IN (SELECT id FROM users WHERE business_id = ?)");
            $stmt->execute([$tenant_id]);

            // Suppliers
            $stmt = $db->prepare("DELETE FROM suppliers WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);

            // Clients
            $stmt = $db->prepare("DELETE FROM clients WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);

            // Products
            $stmt = $db->prepare("DELETE FROM products WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);

            // Brands
            $stmt = $db->prepare("DELETE FROM brands WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);

            // Categories
            $stmt = $db->prepare("DELETE FROM categories WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);

            // No eliminaremos los 'payment_methods' ni 'settings' ni 'users' porque 
            // conforman la estructura básica del negocio ya configurado.

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Sistema restablecido a cero exitosamente']);
        } catch (PDOException $e) {
            $db->rollBack();
            $this->jsonResponse(['success' => false, 'message' => 'Error al restablecer datos: ' . $e->getMessage()], 500);
        }
    }

    public function export_csv() {
        $db = $this->getDb();
        $stmt = $db->query("SELECT p.sku, p.name, p.barcode, p.stock, p.min_stock, p.price, p.unit_cost, c.name as category, b.name as brand FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id ORDER BY p.name ASC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=inventario_' . date('Y-m-d_H-i') . '.csv');
        $output = fopen('php://output', 'w');
        
        // Agregar BOM para que Excel lea UTF-8 correctamente
        fputs($output, "\xEF\xBB\xBF");
        
        fputcsv($output, ['SKU', 'Nombre', 'Código Barra', 'Stock', 'Stock Mínimo', 'Precio', 'Costo Unitario', 'Categoría', 'Marca']);
        foreach ($products as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }

    public function download_template() {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=plantilla_productos.csv');
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Nombre', 'Código Barra', 'Stock', 'Precio', 'Costo', 'Categoría']);
        fputcsv($output, ['Botella de Agua 1L', '123456789012', '50', '2.50', '1.00', 'Bebidas']);
        fclose($output);
        exit;
    }

    public function export_tenant_data() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrador') {
            die("Acceso denegado");
        }
        $db = $this->getDb();
        $tenant_id = $_SESSION['business_id'];

        $data = [
            'export_date' => date('Y-m-d H:i:s'),
            'tenant_id' => $tenant_id,
            'tables' => []
        ];

        // List of tables and conditions to filter by tenant
        $tables = [
            'businesses' => "id = ?",
            'users' => "business_id = ?",
            'categories' => "tenant_id = ?",
            'brands' => "tenant_id = ?",
            'clients' => "tenant_id = ?",
            'suppliers' => "tenant_id = ?",
            'products' => "tenant_id = ?",
            'sales' => "user_id IN (SELECT id FROM users WHERE business_id = ?)",
            'expenses' => "user_id IN (SELECT id FROM users WHERE business_id = ?)"
        ];

        foreach ($tables as $tableName => $condition) {
            $stmt = $db->prepare("SELECT * FROM {$tableName} WHERE {$condition}");
            $stmt->execute([$tenant_id]);
            $fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Remover campos pesados base64 para evitar exceder el límite de Vercel Serverless (4.5MB)
            if ($tableName === 'businesses') {
                foreach ($fetched as &$row) {
                    unset($row['logo_base64']);
                    unset($row['menu_file_base64']);
                }
            }
            $data['tables'][$tableName] = $fetched;
        }
        
        // Also get sale_items for the tenant's sales
        $stmtSaleItems = $db->prepare("SELECT si.* FROM sale_items si JOIN sales s ON si.sale_id = s.id JOIN users u ON s.user_id = u.id WHERE u.business_id = ?");
        $stmtSaleItems->execute([$tenant_id]);
        $data['tables']['sale_items'] = $stmtSaleItems->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=backup_tuinventario_' . date('Y-m-d_H-i') . '.json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
