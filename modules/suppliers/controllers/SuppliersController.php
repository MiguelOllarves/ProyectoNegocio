<?php
require_once __DIR__ . '/../models/Supplier.php';

class SuppliersController extends Controller {
    private $model;

    public function __construct() {
        $this->model = new Supplier();
    }

    public function index() {
        // HTMX carga los proveedores de forma dinámica
        $this->view('modules/suppliers/views/index');
    }

    public function list() {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        $page = (int)($_GET['page'] ?? 1);
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;
        $offset = ($page - 1) * $limit;

        $result = $this->model->paginate($limit, $offset, 'name ASC');
        $suppliers = $result['data'];
        $totalRecords = $result['total'];
        $totalPages = ceil($totalRecords / $limit);
        $baseUrl = BASE_URL . 'suppliers/list';

        if (empty($suppliers)) {
            echo "<tr><td colspan='4' class='p-8 text-center text-gray-400 dark:text-gray-500'><i class='fas fa-truck text-4xl mb-3 block opacity-30'></i>No hay proveedores registrados.</td></tr>";
            return;
        }

        foreach ($suppliers as $s) {
            echo "
            <tr class='hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors animate-fade-in-up'>
                <td class='p-4 font-semibold text-gray-800 dark:text-gray-100'>".htmlspecialchars($s['name'])."</td>
                <td class='p-4 text-sm text-gray-600 dark:text-gray-300'>".htmlspecialchars($s['contact_name'] ?? '-')."</td>
                <td class='p-4 text-sm text-gray-600 dark:text-gray-300'>".htmlspecialchars($s['phone'] ?? '-')."</td>
                <td class='p-4 text-right space-x-1'>
                    <button type='button' onclick='openEditSupplierModal({$s['id']})' class='text-brand-500 hover:text-brand-700 dark:hover:text-brand-400 p-1 mr-2 transition-colors' title='Editar'>
                        <i class='fas fa-edit'></i>
                    </button>
                    <button type='button' onclick='confirmDeleteSupplier({$s['id']})' class='text-red-500 hover:text-red-700 dark:hover:text-red-400 p-1 transition-colors' title='Eliminar'>
                        <i class='fas fa-trash'></i>
                    </button>
                </td>
            </tr>";
        }
        
        $colspan = 4;
        $hxTarget = '#suppliers-tbody';
        require __DIR__ . '/../../../includes/pagination.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'         => $_POST['name'] ?? '',
                'contact_name' => $_POST['contact_name'] ?? '',
                'phone'        => $_POST['phone'] ?? '',
                'email'        => $_POST['email'] ?? '',
                'address'      => $_POST['address'] ?? ''
            ];
            $this->model->create($data);

            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                header('HX-Trigger: suppliersUpdated');
                http_response_code(200);
                exit;
            }

            header('Location: ' . BASE_URL . 'suppliers');
            exit;
        }
        $this->view('modules/suppliers/views/create');
    }

    public function edit($id = null) {
        if (!$id) {
            header('Location: ' . BASE_URL . 'suppliers');
            exit;
        }

        $supplier = $this->model->find($id);
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_GET['json']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
                header('Content-Type: application/json');
                echo json_encode(['supplier' => $supplier], JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: ' . BASE_URL . 'suppliers');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'         => $_POST['name'] ?? '',
                'contact_name' => $_POST['contact_name'] ?? '',
                'phone'        => $_POST['phone'] ?? '',
                'email'        => $_POST['email'] ?? '',
                'address'      => $_POST['address'] ?? ''
            ];
            $this->model->update($id, $data);
            
            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                http_response_code(200);
                exit;
            }
            
            header('Location: ' . BASE_URL . 'suppliers');
            exit;
        }
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            $this->model->delete($_POST['id']);
        }

        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            header('HX-Trigger: suppliersUpdated');
            http_response_code(200);
            exit;
        }

        header('Location: ' . BASE_URL . 'suppliers');
        exit;
    }
}
