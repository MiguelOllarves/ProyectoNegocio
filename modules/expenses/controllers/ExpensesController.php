<?php
require_once __DIR__ . '/../models/Expense.php';

class ExpensesController extends Controller {
    private $model;

    public function __construct() {
        $this->model = new Expense();
    }

    public function index() {
        $this->view('modules/expenses/views/index');
    }

    public function list() {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        $page = (int)($_GET['page'] ?? 1);
        $limit = 5;
        $offset = ($page - 1) * $limit;
        
        $result = $this->model->allWithUserPaginated($limit, $offset);
        $expenses = $result['data'];
        $totalRecords = $result['total'];
        $totalPages = ceil($totalRecords / $limit);
        $baseUrl = BASE_URL . 'expenses/list';

        if (empty($expenses)) {
            echo "<tr><td colspan='6' class='px-6 py-4 text-center text-gray-500 dark:text-gray-400'>No hay gastos registrados.</td></tr>";
            return;
        }

        foreach ($expenses as $e) {
            $date = date('d/m/Y', strtotime($e['expense_date']));
            $cat = htmlspecialchars($e['category']);
            $desc = htmlspecialchars($e['description']);
            $amount = number_format($e['amount'], 2);
            $user = htmlspecialchars($e['user_name'] ?? 'Sistema');
            echo "
            <tr class='hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors'>
                <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white'>{$date}</td>
                <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white'>
                    <span class='px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'>{$cat}</span>
                </td>
                <td class='px-6 py-4 text-sm text-gray-900 dark:text-white'>{$desc}</td>
                <td class='px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600 dark:text-red-400'>\${$amount}</td>
                <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400'>{$user}</td>
                <td class='px-6 py-4 whitespace-nowrap text-right text-sm font-medium'>
                    <button type='button' onclick='openEditExpenseModal({$e['id']})' class='text-brand-500 hover:text-brand-700 dark:hover:text-brand-400 p-1 mr-2 transition-colors' title='Editar'>
                        <i class='fas fa-edit'></i>
                    </button>
                    <button type='button' onclick='confirmDeleteExpense({$e['id']})' class='text-red-600 hover:text-red-900 dark:hover:text-red-400 p-1 transition-colors'>
                        <i class='fas fa-trash'></i>
                    </button>
                </td>
            </tr>
            ";
        }
        
        $colspan = 6;
        $hxTarget = '#expenses-tbody';
        require __DIR__ . '/../../../includes/pagination.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'user_id'     => $_SESSION['user_id'] ?? 1,
                'category'    => $_POST['category'] ?? 'Otro',
                'description' => $_POST['description'] ?? '',
                'amount'      => (float)($_POST['amount'] ?? 0),
                'expense_date'=> $_POST['expense_date'] ?? date('Y-m-d')
            ];

            if ($data['amount'] <= 0) {
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    header('X-Toast-Message: El monto debe ser mayor a 0');
                    header('X-Toast-Type: error');
                    http_response_code(400); 
                    exit;
                }
                exit('Monto inválido');
            }

            $this->model->create($data);
            
            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                http_response_code(200);
                exit;
            }
            
            header('Location: ' . BASE_URL . 'expenses');
            exit;
        }
    }

    public function edit($id = null) {
        if (!$id) {
            header('Location: ' . BASE_URL . 'expenses');
            exit;
        }

        $expense = $this->model->find($id);
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_GET['json']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
                header('Content-Type: application/json');
                echo json_encode(['expense' => $expense], JSON_INVALID_UTF8_IGNORE | JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: ' . BASE_URL . 'expenses');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'category'    => $_POST['category'] ?? 'Otro',
                'description' => $_POST['description'] ?? '',
                'amount'      => (float)($_POST['amount'] ?? 0),
                'expense_date'=> $_POST['expense_date'] ?? date('Y-m-d')
            ];

            if ($data['amount'] <= 0) {
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    header('X-Toast-Message: El monto debe ser mayor a 0');
                    header('X-Toast-Type: error');
                    http_response_code(400); 
                    exit;
                }
                exit('Monto inválido');
            }

            $this->model->update($id, $data);
            
            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                http_response_code(200);
                exit;
            }
            
            header('Location: ' . BASE_URL . 'expenses');
            exit;
        }
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            $this->model->delete($_POST['id']);
        }
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            http_response_code(200);
            exit;
        }
        header('Location: ' . BASE_URL . 'expenses');
        exit;
    }
}
