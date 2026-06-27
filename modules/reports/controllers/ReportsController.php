<?php
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../../inventory/models/Product.php';

class ReportsController extends Controller {
    private $model;
    private $productModel;

    public function __construct() {
        $this->model = new Report();
        $this->productModel = new Product();
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'auth');
            exit;
        }
        
        $tab = $_GET['tab'] ?? 'dashboard'; // 'dashboard' or 'kardex' (requested by sidebar link)
        if ($tab === 'kardex') {
            return $this->kardex();
        }

        $startDate = $_GET['start'] ?? date('Y-m-01');
        $endDate = $_GET['end'] ?? date('Y-m-t');

        $summary = $this->model->getFinancialSummary($startDate, $endDate);
        $salesList = $this->model->getSalesDetail($startDate, $endDate);
        
        $this->view('modules/reports/views/index', [
            'summary' => $summary,
            'sales' => $salesList,
            'start' => $startDate,
            'end' => $endDate
        ]);
    }

    public function kardex() {
        $productId = $_GET['product_id'] ?? null;
        $kardex = $this->model->getKardex($productId);
        $products = $this->productModel->all();

        $this->view('modules/reports/views/kardex', [
            'kardex' => $kardex,
            'products' => $products,
            'selectedProduct' => $productId
        ]);
    }
}
