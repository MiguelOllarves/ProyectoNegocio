<?php
require_once __DIR__ . '/../models/Client.php';

class ClientsController extends Controller {
    private $model;

    public function __construct() {
        $this->model = new Client();
    }

    public function index() {
        $db = \Database::getInstance()->getConnection();
        $stmtWp = $db->prepare("SELECT DISTINCT workplace FROM clients WHERE workplace IS NOT NULL AND workplace != '' ORDER BY workplace ASC");
        $stmtWp->execute();
        $workplaces = $stmtWp->fetchAll(\PDO::FETCH_COLUMN);

        $this->view('modules/clients/views/index', ['workplaces' => $workplaces]);
    }

    public function list() {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        $page = (int)($_GET['page'] ?? 1);
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;
        $offset = ($page - 1) * $limit;

        $result = $this->model->paginate($limit, $offset, 'name ASC');
        $clients = $result['data'];
        $totalRecords = $result['total'];
        $totalPages = ceil($totalRecords / $limit);
        $baseUrl = BASE_URL . 'clients/list';

        if (empty($clients)) {
            echo "<tr><td colspan='5' class='p-8 text-center text-gray-400 dark:text-gray-500'><i class='fas fa-users text-4xl mb-3 block opacity-30'></i>No hay clientes registrados.</td></tr>";
            return;
        }

        foreach ($clients as $c) {
            $name = htmlspecialchars($c['name']);
            $document = htmlspecialchars($c['document'] ?? '-');
            $phone = htmlspecialchars($c['phone'] ?? '-');
            $email = htmlspecialchars($c['email'] ?? '-');
            echo "
            <tr class='hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors'>
                <td class='p-4 text-sm font-bold text-gray-800 dark:text-gray-100'>{$name}</td>
                <td class='p-4 text-sm text-gray-600 dark:text-gray-300'>{$document}</td>
                <td class='p-4 text-sm text-gray-600 dark:text-gray-300'>{$phone}</td>
                <td class='p-4 text-sm text-gray-600 dark:text-gray-300'>{$email}</td>
                <td class='p-4 text-right whitespace-nowrap'>
                    <button type='button' onclick='openViewClientModal({$c['id']})' class='text-blue-500 hover:text-blue-700 dark:hover:text-blue-400 p-1 mr-2 transition-colors' title='Ver Detalles'>
                        <i class='fas fa-eye'></i>
                    </button>
                    <button type='button' onclick='openEditClientModal({$c['id']})' class='text-brand-500 hover:text-brand-700 dark:hover:text-brand-400 p-1 mr-2 transition-colors' title='Editar'>
                        <i class='fas fa-edit'></i>
                    </button>
                    <button type='button' onclick='confirmDeleteClient({$c['id']})' class='text-red-500 hover:text-red-700 dark:hover:text-red-400 p-1 transition-colors' title='Eliminar'>
                        <i class='fas fa-trash'></i>
                    </button>
                </td>
            </tr>
            ";
        }
        
        $colspan = 5;
        $hxTarget = '#clients-tbody';
        require __DIR__ . '/../../../includes/pagination.php';
    }

    public function print() {
        $clients = $this->model->all();
        $this->view('modules/clients/views/print', ['clients' => $clients]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'     => $_POST['name'] ?? '',
                'document' => $_POST['document'] ?? '',
                'phone'    => $_POST['phone'] ?? '',
                'extra_phones' => $_POST['extra_phones'] ?? '',
                'email'    => $_POST['email'] ?? '',
                'address'  => $_POST['address'] ?? '',
                'workplace' => (isset($_POST['workplace_select']) && $_POST['workplace_select'] === 'new') ? ($_POST['workplace_custom'] ?? '') : ($_POST['workplace_select'] ?? ($_POST['workplace'] ?? '')),
                'workplace_component' => $_POST['workplace_component'] ?? '',
                'workplace_detail' => $_POST['workplace_detail'] ?? '',
                'workplace_address' => $_POST['workplace_address'] ?? '',
                'monthly_income' => $_POST['monthly_income'] ?? '',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'gps_location' => $_POST['gps_location'] ?? ''
            ];
            try {
                $this->model->create($data);
                
                // Si es petición HTMX, mandamos un trigger para que la tabla se refresque, y un OK sin redirect
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    header('HX-Trigger: clientsUpdated');
                    http_response_code(200);
                    exit;
                }
            } catch (\Exception $e) {
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    header('X-Toast-Message: Error al crear (¿Documento duplicado?)');
                    header('X-Toast-Type: error');
                    http_response_code(400);
                    exit;
                }
                exit('Error al registrar cliente');
            }
            
            header('Location: ' . BASE_URL . 'clients');
            exit;
        }
        $this->view('modules/clients/views/create');
    }

    public function edit($id = null) {
        if (!$id) {
            header('Location: ' . BASE_URL . 'clients');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_GET['json']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
                // Clean any stray output from includes/warnings
                if (ob_get_level()) ob_end_clean();
                
                header('Content-Type: application/json');
                header('Cache-Control: no-cache, no-store, must-revalidate');
                
                try {
                    $client = $this->model->find($id);
                    echo json_encode(['client' => $client], JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_UNICODE);
                } catch (\Exception $e) {
                    echo json_encode(['client' => null, 'error' => $e->getMessage()]);
                }
                exit;
            }
            header('Location: ' . BASE_URL . 'clients');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name'     => $_POST['name'] ?? '',
                'document' => $_POST['document'] ?? '',
                'phone'    => $_POST['phone'] ?? '',
                'extra_phones' => $_POST['extra_phones'] ?? '',
                'email'    => $_POST['email'] ?? '',
                'address'  => $_POST['address'] ?? '',
                'workplace' => (isset($_POST['workplace_select']) && $_POST['workplace_select'] === 'new') ? ($_POST['workplace_custom'] ?? '') : ($_POST['workplace_select'] ?? ($_POST['workplace'] ?? '')),
                'workplace_component' => $_POST['workplace_component'] ?? '',
                'workplace_detail' => $_POST['workplace_detail'] ?? '',
                'workplace_address' => $_POST['workplace_address'] ?? '',
                'monthly_income' => $_POST['monthly_income'] ?? '',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'gps_location' => $_POST['gps_location'] ?? ''
            ];
            try {
                $this->model->update($id, $data);
                
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    http_response_code(200);
                    exit;
                }
            } catch (\Exception $e) {
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    header('X-Toast-Message: Error al actualizar (posible duplicado)');
                    header('X-Toast-Type: error');
                    http_response_code(400);
                    exit;
                }
                exit('Error al actualizar cliente');
            }
            
            header('Location: ' . BASE_URL . 'clients');
            exit;
        }
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            try {
                $db = \Database::getInstance()->getConnection();
                
                // Buscar si tiene créditos
                $stmt = $db->prepare("SELECT id FROM credits WHERE client_id = ?");
                $stmt->execute([$_POST['id']]);
                $credits = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                
                if (!empty($credits)) {
                    // Borrar pagos asociados a esos créditos
                    $inQuery = implode(',', array_fill(0, count($credits), '?'));
                    $delPayments = $db->prepare("DELETE FROM credit_payments WHERE credit_id IN ($inQuery)");
                    $delPayments->execute($credits);
                    
                    // Borrar los créditos
                    $delCredits = $db->prepare("DELETE FROM credits WHERE client_id = ?");
                    $delCredits->execute([$_POST['id']]);
                }

                $this->model->delete($_POST['id']);
            } catch (\PDOException $e) {
                // If it's a foreign key constraint (23000)
                if ($e->getCode() == '23000' || strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'No se puede eliminar este cliente porque tiene operaciones pendientes que no pudieron ser removidas.']);
                    exit;
                }
                throw $e;
            }
        }
        
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        header('Location: ' . BASE_URL . 'clients');
        exit;
    }

    public function export_csv() {
        $clients = $this->model->all();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=clientes_' . date('Ymd_Hi') . '.csv');
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['ID', 'Nombre', 'Documento', 'Telefono', 'Email', 'Direccion', 'Trabajo', 'Ingreso_Mensual_USD', 'Creado']);
        
        foreach($clients as $c) {
            fputcsv($output, [
                $c['id'],
                $c['name'],
                $c['document'],
                $c['phone'],
                $c['email'],
                $c['address'],
                $c['workplace'],
                $c['monthly_income'],
                $c['created_at']
            ]);
        }
        fclose($output);
        exit;
    }

    public function bulk_import() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!empty($input) && is_array($input)) {
                $successCount = 0;
                $errorCount = 0;
                
                foreach ($input as $row) {
                    $name = trim($row['Nombre'] ?? '');
                    if(empty($name)) { $errorCount++; continue; }
                    
                    $data = [
                        'name' => $name,
                        'document' => trim($row['Documento'] ?? ''),
                        'phone' => trim($row['Telefono'] ?? ''),
                        'email' => trim($row['Email'] ?? ''),
                        'address' => trim($row['Direccion'] ?? ''),
                        'workplace' => trim($row['Trabajo'] ?? ''),
                        'monthly_income' => floatval($row['IngresoMensual'] ?? 0),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                        'user_agent' => 'Bulk Import'
                    ];
                    
                    if ($this->model->create($data)) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'imported' => $successCount, 'errors' => $errorCount]);
                exit;
            }
            
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Payload inválido']);
            exit;
        }
    }
}
