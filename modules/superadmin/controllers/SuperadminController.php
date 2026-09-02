<?php
class SuperadminController extends Controller {
    
    private function requireSuperAdmin() {
        Middleware::requireAuth();
        if ($_SESSION['role'] !== 'super_admin') {
            die("<h2>403 Forbidden - Este módulo es exclusivo para el Desarrollador (SuperAdmin)</h2>");
        }
    }

    public function index() {
        $this->requireSuperAdmin();
        
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();

        // Auto-migración Modo Dios (PostgreSQL)
        $db->exec("CREATE TABLE IF NOT EXISTS site_visits (id SERIAL PRIMARY KEY, ip_address VARCHAR(45), visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        try { $db->exec("ALTER TABLE site_visits ADD COLUMN IF NOT EXISTS country VARCHAR(100)"); } catch(Exception $e){}
        
        $db->exec("CREATE TABLE IF NOT EXISTS banned_ips (
            id SERIAL PRIMARY KEY,
            ip_address VARCHAR(45) UNIQUE NOT NULL,
            reason TEXT,
            banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE IF NOT EXISTS security_alerts (
            id SERIAL PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45),
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NULL,
            business_id INTEGER NULL,
            action VARCHAR(255) NOT NULL,
            target VARCHAR(255) NULL,
            details TEXT NULL,
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Estadísticas Globales
        $stats = [
            'total_tenants' => $db->query("SELECT COUNT(*) FROM businesses")->fetchColumn(),
            'active_trials' => $db->query("SELECT COUNT(*) FROM businesses WHERE subscription_status = 'trial'")->fetchColumn(),
            'expired_accounts' => $db->query("SELECT COUNT(*) FROM businesses WHERE subscription_status = 'expired'")->fetchColumn(),
            'total_income' => $db->query("SELECT SUM(amount) FROM payments WHERE status = 'approved'")->fetchColumn() ?: 0,
            'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_visits' => $db->query("SELECT COUNT(*) FROM site_visits")->fetchColumn(),
            'banned_ips' => $db->query("SELECT COUNT(*) FROM banned_ips")->fetchColumn(),
            'security_alerts' => $db->query("SELECT COUNT(*) FROM security_alerts")->fetchColumn()
        ];



        // Tráfico de últimos 7 días (para la gráfica)
        $daily_visits = [];
        $stmtTraffic = $db->query("
            SELECT DATE(created_at) as day, COUNT(*) as count 
            FROM site_visits 
            WHERE created_at >= NOW() - INTERVAL '30 days'
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");
        $visits_data = $stmtTraffic->fetchAll(PDO::FETCH_ASSOC);
        foreach($visits_data as $v) {
            $daily_visits[$v['day']] = $v['count'];
        }

        // Top Países
        $country_visits = $db->query("SELECT COALESCE(country, 'Desconocido') as country, COUNT(*) as count FROM site_visits GROUP BY country ORDER BY count DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('modules/superadmin/views/index', [
            'stats' => $stats,
            'daily_visits' => $daily_visits,
            'country_visits' => $country_visits
        ]);
    }


    public function tenants() {
        $this->requireSuperAdmin();
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT b.*, 
                       (SELECT COUNT(*) FROM users u WHERE u.business_id = b.id) as subusers_count,
                       (SELECT COUNT(*) FROM products p WHERE p.tenant_id = b.id) as products_count,
                       (SELECT COALESCE(SUM(total), 0) FROM sales s WHERE s.user_id IN (SELECT id FROM users WHERE business_id = b.id) AND s.status = 'completed') as total_sales_amount,
                       (SELECT COUNT(*) FROM sales s WHERE s.user_id IN (SELECT id FROM users WHERE business_id = b.id)) as total_sales_count,
                       (SELECT COUNT(*) FROM expenses e WHERE e.user_id IN (SELECT id FROM users WHERE business_id = b.id)) as expenses_count
                FROM businesses b 
                ORDER BY b.created_at DESC";
        $stmt = $db->query($sql);
        $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->view('modules/superadmin/views/tenants', [
            'tenants' => $tenants
        ]);
    }

    public function toggle_tenant() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $id = $_POST['id'] ?? null;
            $action = $_POST['action'] ?? null; // 'suspend' or 'activate'
            
            if ($id && $action) {
                $status = ($action === 'suspend') ? 'expired' : 'active';
                $db->prepare("UPDATE businesses SET subscription_status = ? WHERE id = ?")->execute([$status, $id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
            }
            exit;
        }
    }

    public function force_password_reset() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $business_id = $_POST['business_id'] ?? null;
            $newPassword = $_POST['new_password'] ?? null;
            
            if ($business_id && $newPassword) {
                $passwordCheck = Middleware::validatePasswordComplexity($newPassword);
                if ($passwordCheck !== true) {
                    echo json_encode(['success' => false, 'message' => $passwordCheck]);
                    exit;
                }
                
                // Modificar SOLO al usuario administrador principal de ese negocio (el que tiene rule = administrador)
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE business_id = ? AND role = 'administrador'");
                $stmt->execute([$hash, $business_id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Contraseña inválida o faltan parámetros.']);
            }
            exit;
        }
    }

    public function users() {
        $this->requireSuperAdmin();
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("
            SELECT u.*, b.business_name 
            FROM users u 
            LEFT JOIN businesses b ON u.business_id = b.id 
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $businesses = $db->query("SELECT id, business_name FROM businesses ORDER BY business_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        $this->view('modules/superadmin/views/users', ['users' => $users, 'businesses' => $businesses]);
    }

    public function toggle_user() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $id = $_POST['id'] ?? null;
            $action = $_POST['action'] ?? null; // 'block' or 'activate'
            
            if ($id && $action) {
                $status = ($action === 'block') ? 0 : 1;
                $db->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'super_admin'")->execute([$status, $id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
            }
            exit;
        }
    }

    public function force_user_password_reset() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $user_id = $_POST['user_id'] ?? null;
            $newPassword = $_POST['new_password'] ?? null;
            
            if ($user_id && $newPassword) {
                $passwordCheck = Middleware::validatePasswordComplexity($newPassword);
                if ($passwordCheck !== true) {
                    echo json_encode(['success' => false, 'message' => $passwordCheck]);
                    exit;
                }
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ? AND role != 'super_admin'");
                $stmt->execute([$hash, $user_id]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Contraseña inválida o faltan parámetros.']);
            }
            exit;
        }
    }

    public function update_user() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $id = $_POST['id'] ?? null;
            $full_name = $_POST['full_name'] ?? '';
            $username = $_POST['username'] ?? '';
            
            $role = $_POST['role'] ?? 'vendedor';
            $business_id = !empty($_POST['business_id']) ? $_POST['business_id'] : null;
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
            
            
            if ($id && $full_name && $username) {
                try {
                    $db->prepare("UPDATE users SET full_name = ?, username = ?, role = ?, business_id = ?, status = ? WHERE id = ? AND role != 'super_admin'")
                       ->execute([$full_name, $username, $role, $business_id, $status, $id]);
                    echo json_encode(['success' => true]);
                } catch(PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'El correo electrónico / credencial ya pertenece a otra persona.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Faltan parámetros obligatorios.']);
            }
            exit;
        }
    }

    public function delete_user() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../../config/Database.php';
            try {
                $db = Database::getInstance()->getConnection();
                $id = $_POST['id'] ?? null;
                if ($id) {
                    try {
                        $db->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'")->execute([$id]);
                        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
                        try {
                            $db->prepare("INSERT INTO audit_logs(user_id, action, target, ip_address) VALUES (?, 'Hard Delete', ?, ?)")->execute([$_SESSION['user_id'] ?? null, "User ID $id", $ip]);
                        } catch(Exception $ex) {}
                        echo json_encode(['success' => true]);
                    } catch(PDOException $e) {
                        // Soft Delete Fallback
                        $db->prepare("UPDATE users SET status = 0 WHERE id = ? AND role != 'super_admin'")->execute([$id]);
                        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
                        try {
                            $db->prepare("INSERT INTO audit_logs(user_id, action, target, ip_address, details) VALUES (?, 'Soft Delete (Fallo FK)', ?, ?, 'Inhabilitado por histórico')")->execute([$_SESSION['user_id'] ?? null, "User ID $id", $ip]);
                        } catch(Exception $ex) {}
                        echo json_encode(['success' => true, 'message' => 'Usuario desactivado permanentemente (Soft Delete) porque posee histórico e hitos.']);
                    }
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Internal Error: ' . $e->getMessage()]);
            }
            exit;
        }
    }


    public function backups() {
        $this->requireSuperAdmin();
        $this->view('modules/superadmin/views/backups');
    }

    public function profile() {
        $this->requireSuperAdmin();
        
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        $this->view('modules/superadmin/views/profile', ['user' => $user]);
    }

    public function update_profile() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $new_username = $_POST['username'] ?? '';
            $new_password = $_POST['password'] ?? '';
            
            if (empty($new_username)) {
                echo json_encode(['success' => false, 'message' => 'El identificador (usuario) no puede estar vacío.']);
                exit;
            }
            
            try {
                if (!empty($new_password)) {
                    $passwordCheck = Middleware::validatePasswordComplexity($new_password);
                    if ($passwordCheck !== true) {
                        echo json_encode(['success' => false, 'message' => $passwordCheck]);
                        exit;
                    }
                    $hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                    $stmt->execute([$new_username, $hash, $_SESSION['user_id']]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET username = ? WHERE id = ?");
                    $stmt->execute([$new_username, $_SESSION['user_id']]);
                }
                $_SESSION['username'] = $new_username;
                echo json_encode(['success' => true]);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Error: El usuario ya existe o error en BD.']);
            }
            exit;
        }
    }

    public function backup_db() {
        $this->requireSuperAdmin();
        echo "El volcado de PostgreSQL/MySQL debe gestionarse externamente (pg_dump) por razones de seguridad en este servidor.";
        exit;
    }


    // ════════════════════════════════════════════════════════════════════════════
    // MODO DIOS: MÉTODOS DE GESTIÓN AVANZADA
    // ════════════════════════════════════════════════════════════════════════════



    public function security() {
        $this->requireSuperAdmin();
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $bannedIps = $db->query("SELECT * FROM banned_ips ORDER BY banned_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        $alerts = $db->query("SELECT * FROM security_alerts ORDER BY created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
        
        $this->view('modules/superadmin/views/security', [
            'banned_ips' => $bannedIps,
            'alerts' => $alerts
        ]);
    }

    public function impersonate() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $targetUserId = $_POST['user_id'] ?? 0;
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role != 'super_admin'");
            $stmt->execute([$targetUserId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Registrar impersonación en auditoría
                $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
                $db->prepare("INSERT INTO audit_logs(user_id, action, target, ip_address, details) VALUES (?, 'Impersonate', ?, ?, ?)")->execute([$_SESSION['user_id'] ?? null, "User ID " . $user['id'], $ip, "S-Admin suplantando a " . $user['username']]);
                
                // BACKUP SESSION
                $_SESSION['superadmin_snapshot'] = [
                    'user_id' => $_SESSION['user_id'],
                    'business_id' => $_SESSION['business_id'] ?? null,
                    'username' => $_SESSION['username'],
                    'role' => $_SESSION['role'],
                    'full_name' => $_SESSION['full_name'],
                    'business_slug' => $_SESSION['business_slug'] ?? null
                ];

                // Setear sesión del suplantado
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['business_id'] = $user['business_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Obtener slug del negocio si existe
                if ($user['business_id']) {
                    $stmtB = $db->prepare("SELECT slug FROM businesses WHERE id = ?");
                    $stmtB->execute([$user['business_id']]);
                    $_SESSION['business_slug'] = $stmtB->fetchColumn();
                } else {
                    unset($_SESSION['business_slug']);
                }
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o restringido']);
            }
        }
        exit;
    }

    public function unimpersonate() {
        if (!isset($_SESSION['superadmin_snapshot'])) {
            header("Location: " . BASE_URL);
            exit;
        }
        
        $snap = $_SESSION['superadmin_snapshot'];
        $_SESSION['user_id'] = $snap['user_id'];
        $_SESSION['business_id'] = $snap['business_id'];
        $_SESSION['username'] = $snap['username'];
        $_SESSION['role'] = $snap['role'];
        $_SESSION['full_name'] = $snap['full_name'];
        $_SESSION['business_slug'] = $snap['business_slug'];
        
        unset($_SESSION['superadmin_snapshot']);
        header("Location: " . BASE_URL . "superadmin/users");
        exit;
    }

    public function changeUserPassword() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_POST['user_id'] ?? 0;
            $newPass = $_POST['new_password'] ?? '';
            
            $passwordCheck = Middleware::validatePasswordComplexity($newPass);
            if ($passwordCheck !== true) {
                echo json_encode(['success' => false, 'message' => $passwordCheck]);
                exit;
            }
            
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ? AND role != 'super_admin'");
            
            if ($stmt->execute([$hash, $userId]) && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar (quizás intentas cambiar al superadmin)']);
            }
        }
        exit;
    }

    public function banIp() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ip = $_POST['ip_address'] ?? '';
            $reason = $_POST['reason'] ?? 'Bloqueo manual desde Modo Dios';
            
            if ($ip) {
                require_once __DIR__ . '/../../../config/Database.php';
                $db = Database::getInstance()->getConnection();
                try {
                    $db->prepare("INSERT INTO banned_ips (ip_address, reason) VALUES (?, ?)")->execute([$ip, $reason]);
                    echo json_encode(['success' => true]);
                } catch(Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Error o IP ya baneada']);
                }
            }
        }
        exit;
    }

    public function unbanIp() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ip = $_POST['ip_address'] ?? '';
            if ($ip) {
                require_once __DIR__ . '/../../../config/Database.php';
                $db = Database::getInstance()->getConnection();
                $db->prepare("DELETE FROM banned_ips WHERE ip_address = ?")->execute([$ip]);
                echo json_encode(['success' => true]);
            }
        }
        exit;
    }

    public function resetVisits() {
        $this->requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $db->exec("DELETE FROM site_visits");
            echo json_encode(['success' => true]);
        }
        exit;
    }
}

