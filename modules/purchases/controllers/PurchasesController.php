<?php
require_once __DIR__ . '/../models/Purchase.php';
require_once __DIR__ . '/../../inventory/models/Product.php';
require_once __DIR__ . '/../../suppliers/models/Supplier.php';

class PurchasesController extends Controller {
    private $model;
    private $productModel;
    private $supplierModel;

    public function __construct() {
        $this->model = new Purchase();
        $this->productModel = new Product();
        $this->supplierModel = new Supplier();
    }

    public function index() {
        $purchases = $this->model->allWithSupplier();
        $products = $this->productModel->all();
        $suppliers = $this->supplierModel->all();
        $this->view('modules/purchases/views/index', [
            'purchases' => $purchases,
            'products' => $products,
            'suppliers' => $suppliers
        ]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!empty($data['items'])) {
                $userId = $_SESSION['user_id'] ?? 1;
                $supplierId = $data['supplier_id'] ?? null;
                $notes = $data['notes'] ?? '';

                $purchaseId = $this->model->createWithItems($userId, $supplierId, $data['items'], $notes);

                if ($purchaseId) {
                    $this->jsonResponse(['success' => true, 'purchase_id' => $purchaseId]);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Error al procesar la compra'], 500);
                }
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'No hay productos en la compra'], 400);
            }
            return;
        }

        $products = $this->productModel->all();
        $suppliers = $this->supplierModel->all();
        $this->view('modules/purchases/views/create', [
            'products' => $products,
            'suppliers' => $suppliers
        ]);
    }
}
