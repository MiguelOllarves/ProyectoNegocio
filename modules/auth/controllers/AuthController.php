<?php
class AuthController extends Controller {
    public function index() {
        // Si ya está logueado, redirigir al dashboard
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }
        $this->view('modules/users/views/login');
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :usr LIMIT 1");
            $stmt->execute(['usr' => $username]);
            $user = $stmt->fetch();
            
            // Usando verify de Bcrypt (el hash se creo con password_hash de PHP)
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                
                header('Location: ' . BASE_URL . 'dashboard');
                exit;
            } else {
                $this->view('modules/users/views/login', ['error' => 'Credenciales inválidas. Intente de nuevo.']);
            }
        }
    }
    
    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . 'auth');
        exit;
    }
}
