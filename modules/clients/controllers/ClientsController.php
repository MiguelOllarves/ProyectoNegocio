<?php
require_once __DIR__ . '/../models/Client.php';

class ClientsController extends Controller {
    private $model;

    public function __construct() {
        $this->model = new Client();
    }

    public function index() {
        // No cargamos los clientes aquí porque HTMX los cargará dinámicamente via 'list'
        $this->view('modules/clients/views/index');
    }

    public function list() {
        $clients = $this->model->all();
        if (empty($clients)) {
            echo "<tr><td colspan='5' class='p-8 text-center text-gray-400 dark:text-gray-500'><i class='fas fa-users text-4xl mb-3 block opacity-30'></i>No hay clientes registrados.</td></tr>";
            return;
        }

        foreach ($clients as $c) {
            echo "
            <tr class='hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors animate-fade-in-up'>
                <td class='p-4 font-semibold text-gray-800 dark:text-gray-100'>".htmlspecialchars($c['name'])."</td>
                <td class='p-4 text-sm text-gray-600 dark:text-gray-300'>".htmlspecialchars($c['document'] ?? '-')."</td>
                <td class='p-4 text-sm text-gray-600 dark:text-gray-300'>".htmlspecialchars($c['phone'] ?? '-')."</td>
                <td class='p-4 text-sm text-gray-600 dark:text-gray-300'>".htmlspecialchars($c['email'] ?? '-')."</td>
                <td class='p-4 text-right space-x-1'>
                    <a href='".BASE_URL."clients/edit/{$c['id']}' class='inline-block text-gray-400 hover:text-amber-500 bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg transition-colors' title='Editar'><i class='fas fa-edit'></i></a>
                    <form hx-post='".BASE_URL."clients/delete' hx-swap='none' hx-confirm='¿Eliminar este cliente?' class='inline'>
                        <input type='hidden' name='id' value='{$c['id']}'>
                        <button type='submit' class='text-gray-400 hover:text-red-500 bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg transition-colors' title='Eliminar'><i class='fas fa-trash'></i></button>
                    </form>
                </td>
            </tr>";
        }
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'     => $_POST['name'] ?? '',
                'document' => $_POST['document'] ?? '',
                'phone'    => $_POST['phone'] ?? '',
                'email'    => $_POST['email'] ?? '',
                'address'  => $_POST['address'] ?? ''
            ];
            $this->model->create($data);
            
            // Si es petición HTMX, mandamos un trigger para que la tabla se refresque, y un OK sin redirect
            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                header('HX-Trigger: clientsUpdated');
                http_response_code(200);
                exit;
            }
            
            header('Location: ' . BASE_URL . 'clients');
            exit;
        }
        $this->view('modules/clients/views/create');
    }

    public function edit($id) {
        $client = $this->model->find($id);
        if (!$client) { header('Location: ' . BASE_URL . 'clients'); exit; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'     => $_POST['name'] ?? '',
                'document' => $_POST['document'] ?? '',
                'phone'    => $_POST['phone'] ?? '',
                'email'    => $_POST['email'] ?? '',
                'address'  => $_POST['address'] ?? ''
            ];
            $this->model->update($id, $data);
            header('Location: ' . BASE_URL . 'clients');
            exit;
        }
        $this->view('modules/clients/views/create', ['client' => $client]);
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            $this->model->delete($_POST['id']);
        }
        
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            header('HX-Trigger: clientsUpdated');
            http_response_code(200);
            exit;
        }

        header('Location: ' . BASE_URL . 'clients');
        exit;
    }
}
