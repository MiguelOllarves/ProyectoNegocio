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

    public function print($id = null) {
        if ($id) {
            $business_id = $_SESSION['business_id'] ?? 1;
            $purchase = $this->model->findWithItems($id, $business_id);
            if (!$purchase) {
                echo "Compra no encontrada.";
                return;
            }
            $this->view('modules/purchases/views/print_single', ['purchase' => $purchase]);
        } else {
            $purchases = $this->model->allWithSupplier();
            $this->view('modules/purchases/views/print', ['purchases' => $purchases]);
        }
    }

    public function viewDetails($id) {
        $business_id = $_SESSION['business_id'] ?? 1;
        $purchase = $this->model->findWithItems($id, $business_id);
        if ($purchase) {
            $this->jsonResponse(['success' => true, 'data' => $purchase]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'No encontrada'], 404);
        }
    }

    public function delete($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'] ?? 1;
            $business_id = $_SESSION['business_id'] ?? 1;
            try {
                if ($this->model->deleteWithReversal($id, $userId, $business_id)) {
                    $this->jsonResponse(['success' => true]);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'No se pudo anular la compra'], 400);
                }
            } catch (\Exception $e) {
                $this->jsonResponse(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()], 500);
            }
        }
    }

    public function edit($id) {
        $business_id = $_SESSION['business_id'] ?? 1;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!empty($data['items'])) {
                $userId = $_SESSION['user_id'] ?? 1;
                $supplierId = $data['supplier_id'] ?? null;
                $notes = $data['notes'] ?? '';

                try {
                    $success = $this->model->updateWithItems($id, $userId, $supplierId, $data['items'], $notes, $business_id);
                    if ($success) {
                        $this->jsonResponse(['success' => true]);
                    } else {
                        $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar'], 500);
                    }
                } catch (\Exception $e) {
                    $this->jsonResponse(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()], 500);
                }
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'No hay productos'], 400);
            }
            return;
        }

        // Output view for edit (not strictly required if done via React/Alpine Modal, but good to have)
        // Or we can just render via modal on the frontend fetching `viewDetails`.
        $purchase = $this->model->findWithItems($id, $business_id);
        if (!$purchase) {
            header('Location: ' . BASE_URL . 'purchases');
            exit;
        }
        $products = $this->productModel->all();
        $suppliers = $this->supplierModel->all();
        $this->view('modules/purchases/views/edit', [
            'purchase' => $purchase,
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

                try {
                    $purchaseId = $this->model->createWithItems($userId, $supplierId, $data['items'], $notes);
                    if ($purchaseId) {
                        $this->jsonResponse(['success' => true, 'purchase_id' => $purchaseId]);
                    } else {
                        $this->jsonResponse(['success' => false, 'message' => 'Error indeterminado al procesar la compra'], 500);
                    }
                } catch (\Exception $e) {
                    $this->jsonResponse(['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()], 500);
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
