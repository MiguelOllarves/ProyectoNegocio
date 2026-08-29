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

        // Require 'reports' permission for the financial dashboard
        require_once __DIR__ . '/../../../core/Middleware.php';
        Middleware::requirePermission('reports');

        $startDate = $_GET['start'] ?? date('Y-m-01');
        $endDate = $_GET['end'] ?? date('Y-m-t');

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;
        $offset = ($page - 1) * $limit;

        $summary = $this->model->getFinancialSummary($startDate, $endDate);
        
        $totalSales = $this->model->getSalesCount($startDate, $endDate);
        $totalPages = ceil($totalSales / $limit);
        if ($totalPages == 0) $totalPages = 1;
        
        $salesList = $this->model->getSalesDetailPaginated($startDate, $endDate, $limit, $offset);
        
        $this->view('modules/reports/views/index', [
            'summary' => $summary,
            'sales' => $salesList,
            'start' => $startDate,
            'end' => $endDate,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'totalSales' => $totalSales
        ]);
    }

    public function print_sales() {
        $startDate = $_GET['start'] ?? date('Y-m-01');
        $endDate = $_GET['end'] ?? date('Y-m-t');

        $summary = $this->model->getFinancialSummary($startDate, $endDate);
        $salesList = $this->model->getSalesDetail($startDate, $endDate);
        
        $this->view('modules/reports/views/print_sales', [
            'summary' => $summary,
            'sales' => $salesList,
            'start' => $startDate,
            'end' => $endDate
        ]);
    }

    public function kardex() {
        require_once __DIR__ . '/../../../core/Middleware.php';
        // Kardex is accessible by users with inventory OR reports permission
        $role = $_SESSION['role'] ?? '';
        $perms = $_SESSION['permissions'] ?? [];
        if ($role !== 'administrador' && $role !== 'super_admin' && !in_array('inventory', $perms) && !in_array('reports', $perms)) {
            FlashMessage::set('error', 'Acceso denegado: No tienes permiso para ver el Kardex.');
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }

        $productId = $_GET['product_id'] ?? null;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;
        $offset = ($page - 1) * $limit;

        $totalKardex = $this->model->getKardexCount($productId);
        $totalPages = ceil($totalKardex / $limit);
        if ($totalPages == 0) $totalPages = 1;

        $kardex = $this->model->getKardexPaginated($productId, $limit, $offset);
        $products = $this->productModel->all();

        $this->view('modules/reports/views/kardex', [
            'kardex' => $kardex,
            'products' => $products,
            'selectedProduct' => $productId,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'totalRecords' => $totalKardex
        ]);
    }
    
    public function print_kardex() {
        $productId = $_GET['product_id'] ?? null;
        $kardex = $this->model->getKardex($productId);
        $this->view('modules/reports/views/print_kardex', [
            'kardex' => $kardex,
            'selectedProduct' => $productId
        ]);
    }

    public function auditoria() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'auth');
            exit;
        }
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 50;
        $offset = ($page - 1) * $limit;

        $totalLogs = $this->model->getAuditLogsCount();
        $totalPages = ceil($totalLogs / $limit);
        if ($totalPages == 0) $totalPages = 1;

        $logs = $this->model->getAuditLogsPaginated($limit, $offset);
        $this->view('modules/reports/views/audit', [
            'logs' => $logs,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'totalLogs' => $totalLogs
        ]);
    }
    
    public function print_audit() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'auth');
            exit;
        }
        $logs = $this->model->getAuditLogs();
        $this->view('modules/reports/views/print_audit', ['logs' => $logs]);
    }
}
