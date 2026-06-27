<?php
require_once __DIR__ . '/../models/Expense.php';

class ExpensesController extends Controller {
    private $model;

    public function __construct() {
        $this->model = new Expense();
    }

    public function index() {
        $expenses = $this->model->allWithUser();
        $this->view('modules/expenses/views/index', ['expenses' => $expenses]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'user_id'     => $_SESSION['user_id'] ?? 1,
                'category'    => $_POST['category'] ?? 'Otro',
                'description' => $_POST['description'] ?? '',
                'amount'      => $_POST['amount'] ?? 0,
                'expense_date'=> $_POST['expense_date'] ?? date('Y-m-d')
            ];
            $this->model->create($data);
            header('Location: ' . BASE_URL . 'expenses');
            exit;
        }
        $this->view('modules/expenses/views/create');
    }

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            $this->model->delete($_POST['id']);
        }
        header('Location: ' . BASE_URL . 'expenses');
        exit;
    }
}
