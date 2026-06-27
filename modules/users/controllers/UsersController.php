<?php
class UsersController extends Controller {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'auth');
            exit;
        }

        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $userId = $_SESSION['user_id'];
        
        // Obtenemos los datos del usuario actual
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Obtenemos su historial de auditoría de los últimos 30 movimientos
        $stmtAudit = $db->prepare("SELECT * FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
        $stmtAudit->execute([$userId]);
        $auditLogs = $stmtAudit->fetchAll(PDO::FETCH_ASSOC);

        $this->view('modules/users/views/profile', [
            'user' => $user,
            'auditLogs' => $auditLogs,
            'success' => $_SESSION['profile_success'] ?? null,
            'error' => $_SESSION['profile_error'] ?? null
        ]);
        
        // Limpiamos mensajes flash
        unset($_SESSION['profile_success']);
        unset($_SESSION['profile_error']);
    }

    public function updatePassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $_SESSION['profile_error'] = "Todos los campos son obligatorios.";
                header('Location: ' . BASE_URL . 'users');
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                $_SESSION['profile_error'] = "Las contraseñas nuevas no coinciden.";
                header('Location: ' . BASE_URL . 'users');
                exit;
            }
            
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'];
            
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $hash = $stmt->fetchColumn();
            
            if (password_verify($currentPassword, $hash)) {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$newHash, $userId]);
                
                $_SESSION['profile_success'] = "Contraseña actualizada exitosamente.";
            } else {
                $_SESSION['profile_error'] = "La contraseña actual es incorrecta.";
            }
            
            header('Location: ' . BASE_URL . 'users');
            exit;
        }
    }
}
