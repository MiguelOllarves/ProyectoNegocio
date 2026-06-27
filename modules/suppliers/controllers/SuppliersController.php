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
        $suppliers = $this->model->all();
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
                    <a href='".BASE_URL."suppliers/edit/{$s['id']}' class='inline-block text-gray-400 hover:text-amber-500 bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg transition-colors' title='Editar'><i class='fas fa-edit'></i></a>
                    <form hx-post='".BASE_URL."suppliers/delete' hx-swap='none' hx-confirm='¿Eliminar este proveedor?' class='inline'>
                        <input type='hidden' name='id' value='{$s['id']}'>
                        <button type='submit' class='text-gray-400 hover:text-red-500 bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg transition-colors' title='Eliminar'><i class='fas fa-trash'></i></button>
                    </form>
                </td>
            </tr>";
        }
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

    public function edit($id) {
        $supplier = $this->model->find($id);
        if (!$supplier) { header('Location: ' . BASE_URL . 'suppliers'); exit; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'         => $_POST['name'] ?? '',
                'contact_name' => $_POST['contact_name'] ?? '',
                'phone'        => $_POST['phone'] ?? '',
                'email'        => $_POST['email'] ?? '',
                'address'      => $_POST['address'] ?? ''
            ];
            $this->model->update($id, $data);
            header('Location: ' . BASE_URL . 'suppliers');
            exit;
        }
        $this->view('modules/suppliers/views/create', ['supplier' => $supplier]);
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
