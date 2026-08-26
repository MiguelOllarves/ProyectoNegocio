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
        
        // Obtenemos los datos del usuario actual y su negocio si aplica
        $stmt = $db->prepare("SELECT u.*, b.business_name, b.rif, b.owner_phone, b.business_phone, b.document_id, b.email, b.category 
                              FROM users u 
                              LEFT JOIN businesses b ON u.business_id = b.id 
                              WHERE u.id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si es admin, cargamos sub-usuarios de su negocio
        $subUsers = [];
        if ($user['role'] === 'administrador' && !empty($user['business_id'])) {
            $stmtSub = $db->prepare("SELECT id, username, full_name, role, status, created_at FROM users WHERE business_id = ? AND id != ? ORDER BY id DESC");
            $stmtSub->execute([$user['business_id'], $userId]);
            $subUsers = $stmtSub->fetchAll(PDO::FETCH_ASSOC);
        }

        // Cargar historial de sesiones de login
        $loginSessions = [];
        try {
            $stmtSessions = $db->prepare("SELECT * FROM login_sessions WHERE user_id = ? ORDER BY logged_in_at DESC LIMIT 20");
            $stmtSessions->execute([$userId]);
            $loginSessions = $stmtSessions->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            // Tabla aún no creada, ignorar
        }

        $this->view('modules/users/views/profile', [
            'user' => $user,
            'subUsers' => $subUsers,
            'loginSessions' => $loginSessions,
            'success' => $_SESSION['profile_success'] ?? null,
            'error' => $_SESSION['profile_error'] ?? null
        ]);
        
        // Limpiamos mensajes flash
        unset($_SESSION['profile_success']);
        unset($_SESSION['profile_error']);
    }

    public function storeSubUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['role'] ?? '') === 'administrador') {
            $username = $_POST['username'] ?? ''; // Será la cédula
            $fullName = $_POST['full_name'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] ?? 'vendedor';
            $businessId = $_SESSION['business_id'];
            
            $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
            $permissionsJson = json_encode($permissions);

            if (empty($username) || empty($password) || empty($fullName)) {
                $this->jsonResponse(['success' => false, 'message' => 'Faltan campos obligatorios'], 400);
            }

            if ($password !== $confirmPassword) {
                $this->jsonResponse(['success' => false, 'message' => 'Las contraseñas no coinciden'], 400);
            }
            
            $passwordCheck = Middleware::validatePasswordComplexity($password);
            if ($passwordCheck !== true) {
                $this->jsonResponse(['success' => false, 'message' => $passwordCheck], 400);
            }

            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();

            // Check if username (cedula) exists
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmtCheck->execute([$username]);
            if ($stmtCheck->fetch()) {
                $this->jsonResponse(['success' => false, 'message' => 'Esta cédula ya está registrada en el sistema'], 400);
            }

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (business_id, username, full_name, password, role, status, permissions_json) VALUES (?, ?, ?, ?, ?, 1, ?)");
            
            if ($stmt->execute([$businessId, $username, $fullName, $hashed, $role, $permissionsJson])) {
                $this->jsonResponse(['success' => true, 'message' => 'Sub-usuario creado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al crear usuario'], 500);
            }
        }
    }

    public function toggleStatus($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['role'] ?? '') === 'administrador') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $businessId = $_SESSION['business_id'];

            // Ensure the user belongs to the same business
            $stmt = $db->prepare("UPDATE users SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE id = ? AND business_id = ? AND role != 'administrador'");
            if ($stmt->execute([$id, $businessId])) {
                $this->jsonResponse(['success' => true]);
            } else {
                $this->jsonResponse(['success' => false], 500);
            }
        }
    }

    public function getSubUser($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_SESSION['role'] ?? '') === 'administrador') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $businessId = $_SESSION['business_id'];

            $stmt = $db->prepare("SELECT id, username, full_name, role, permissions_json FROM users WHERE id = ? AND business_id = ? AND role != 'administrador'");
            $stmt->execute([$id, $businessId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $this->jsonResponse(['success' => true, 'user' => $user]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            }
        }
    }

    public function updateSubUser($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['role'] ?? '') === 'administrador') {
            $username = $_POST['username'] ?? '';
            $fullName = $_POST['full_name'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $role = $_POST['role'] ?? 'vendedor';
            $businessId = $_SESSION['business_id'];
            
            $permissions = isset($_POST['permissions']) && is_array($_POST['permissions']) ? $_POST['permissions'] : [];
            $permissionsJson = json_encode($permissions);

            if (empty($username) || empty($fullName)) {
                $this->jsonResponse(['success' => false, 'message' => 'Cédula y Nombre son obligatorios'], 400);
            }

            if (!empty($password)) {
                if ($password !== $confirmPassword) {
                    $this->jsonResponse(['success' => false, 'message' => 'Las contraseñas no coinciden'], 400);
                }
                
                $passwordCheck = Middleware::validatePasswordComplexity($password);
                if ($passwordCheck !== true) {
                    $this->jsonResponse(['success' => false, 'message' => $passwordCheck], 400);
                }
            }

            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();

            // Verificar cédula única
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmtCheck->execute([$username, $id]);
            if ($stmtCheck->fetch()) {
                $this->jsonResponse(['success' => false, 'message' => 'Esta cédula ya está registrada en otro usuario'], 400);
            }

            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = ?, full_name = ?, password = ?, role = ?, permissions_json = ? WHERE id = ? AND business_id = ? AND role != 'administrador'");
                $success = $stmt->execute([$username, $fullName, $hashed, $role, $permissionsJson, $id, $businessId]);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, full_name = ?, role = ?, permissions_json = ? WHERE id = ? AND business_id = ? AND role != 'administrador'");
                $success = $stmt->execute([$username, $fullName, $role, $permissionsJson, $id, $businessId]);
            }

            if ($success) {
                $this->jsonResponse(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar usuario'], 500);
            }
        }
    }

    public function deleteSubUser($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['role'] ?? '') === 'administrador') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $businessId = $_SESSION['business_id'];

            $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND business_id = ? AND role != 'administrador'");
            if ($stmt->execute([$id, $businessId])) {
                $this->jsonResponse(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar usuario'], 500);
            }
        }
    }

    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $newUsername = trim($_POST['username'] ?? ''); // Será la cédula
            $newFullName = trim($_POST['full_name'] ?? '');
            
            // Campos de Negocio / Registro
            $newEmail = trim($_POST['email'] ?? '');
            $newOwnerPhone = trim($_POST['owner_phone'] ?? '');
            $newRif = trim($_POST['rif'] ?? '');
            $newBusinessName = trim($_POST['business_name'] ?? '');
            $newBusinessPhone = trim($_POST['business_phone'] ?? '');
            
            if (empty($newUsername)) {
                $_SESSION['profile_error'] = "La cédula no puede estar vacía.";
                header('Location: ' . BASE_URL . 'users');
                exit;
            }
            
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'];
            
            // Verificar si el username ya existe en otro usuario
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmtCheck->execute([$newUsername, $userId]);
            if ($stmtCheck->fetch()) {
                $_SESSION['profile_error'] = "Ese usuario o cédula ya está en uso.";
                header('Location: ' . BASE_URL . 'users');
                exit;
            }
            
            try {
                $db->beginTransaction();
                
                $updateStmt = $db->prepare("UPDATE users SET username = ?, full_name = ? WHERE id = ?");
                $updateStmt->execute([$newUsername, $newFullName, $userId]);
                
                // Si es administrador, actualizamos los datos del negocio (document_id = username)
                if (($_SESSION['role'] ?? '') === 'administrador' && !empty($_SESSION['business_id'])) {
                    $updBiz = $db->prepare("UPDATE businesses SET email = ?, document_id = ?, owner_phone = ?, rif = ?, business_name = ?, business_phone = ? WHERE id = ?");
                    $updBiz->execute([$newEmail, $newUsername, $newOwnerPhone, $newRif, $newBusinessName, $newBusinessPhone, $_SESSION['business_id']]);
                }
                
                $db->commit();
                
                $_SESSION['username'] = $newUsername;
                if (!empty($newFullName)) $_SESSION['full_name'] = $newFullName;
                $_SESSION['profile_success'] = "Perfil actualizado exitosamente.";
                
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['profile_error'] = "Error al actualizar el usuario: " . $e->getMessage();
            }
            
            header('Location: ' . BASE_URL . 'users');
            exit;
        }
    }

    public function updatePassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($newPassword) || empty($confirmPassword)) {
                $_SESSION['profile_error'] = "Todos los campos son obligatorios.";
                header('Location: ' . BASE_URL . 'users');
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                $_SESSION['profile_error'] = "Las contraseñas nuevas no coinciden.";
                header('Location: ' . BASE_URL . 'users');
                exit;
            }
            
            $passwordCheck = Middleware::validatePasswordComplexity($newPassword);
            if ($passwordCheck !== true) {
                $_SESSION['profile_error'] = $passwordCheck;
                header('Location: ' . BASE_URL . 'users');
                exit;
            }
            
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'];
            
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$newHash, $userId]);
            
            $_SESSION['profile_success'] = "Contraseña actualizada exitosamente.";

            header('Location: ' . BASE_URL . 'users');
            exit;
        }
    }

    public function closeOtherSessions() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $userId = $_SESSION['user_id'];
            $currentSessionId = session_id();

            try {
                $stmt = $db->prepare("SELECT active_session_id FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $activeSessionId = $stmt->fetchColumn();
            } catch (Exception $e) {
                $this->jsonResponse(['success' => false, 'message' => 'Error al consultar sesiones'], 500);
                return;
            }

            if (!empty($activeSessionId) && $activeSessionId !== $currentSessionId) {
                try {
                    $stmtKill = $db->prepare("DELETE FROM sessions WHERE id = ?");
                    $stmtKill->execute([$activeSessionId]);
                } catch (Exception $e) {}
            }

            // La sesión actual pasa a ser la única activa
            try {
                $stmtUpdate = $db->prepare("UPDATE users SET active_session_id = ? WHERE id = ?");
                $stmtUpdate->execute([$currentSessionId, $userId]);
            } catch (Exception $e) {}

            $this->jsonResponse(['success' => true, 'message' => 'Se cerró la sesión abierta en otros dispositivos. Este equipo es ahora el único activo.']);
        }
    }
}
