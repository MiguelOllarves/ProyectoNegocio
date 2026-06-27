<?php
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../../inventory/models/Product.php';

class SalesController extends Controller {
    private $saleModel;
    private $productModel;

    public function __construct() {
        $this->saleModel = new Sale();
        $this->productModel = new Product();
    }

    public function index() {
        $products = $this->productModel->all();
        
        // Leer TODAS las configuraciones fiscales desde la BD (no hardcoded)
        $bcvRate    = (float) Settings::get('bcv_rate', 622.21);
        $ivaRate    = (float) Settings::get('tax_iva', 16);     // Porcentaje: 16
        $igtfRate   = (float) Settings::get('tax_igtf', 3);     // Porcentaje: 3
        $ivaMethod  = Settings::get('iva_method', 'included');   // 'included' o 'add_later'

        // Leer métodos de pago activos desde la BD
        $db = Database::getInstance()->getConnection();
        $pmStmt = $db->query("SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY id");
        $paymentMethods = $pmStmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('modules/sales/views/index', [
            'products'        => $products,
            'bcvRate'         => $bcvRate,
            'ivaRate'         => $ivaRate,
            'igtfRate'        => $igtfRate,
            'ivaMethod'       => $ivaMethod,
            'paymentMethods'  => $paymentMethods,
        ]);
    }

    public function process() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!empty($data['items']) && isset($data['totals'])) {
                $userId = $_SESSION['user_id'] ?? 1; 
                
                $totals   = $data['totals'];
                $payments = $data['payments'] ?? [];

                $saleId = $this->saleModel->createSale(
                    $userId,
                    $totals['subtotalUsd'] ?? 0,
                    $totals['ivaUsd'] ?? 0,
                    $totals['igtfUsd'] ?? 0,
                    $totals['totalUsd'] ?? 0,
                    $totals['paidUsd'] ?? 0,
                    $totals['changeUsd'] ?? 0,
                    $data['items'],
                    $payments
                );
                
                if ($saleId) {
                    $this->jsonResponse(['success' => true, 'sale_id' => $saleId]);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Error en base de datos'], 500);
                }
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos o carrito vacío'], 400);
            }
        }
    }

    public function receipt($id) {
        $this->view('modules/sales/views/receipt', ['sale_id' => $id]);
    }
}
