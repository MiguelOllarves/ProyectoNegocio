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

        // Auto-migración Modo Dios
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $db->exec("CREATE TABLE IF NOT EXISTS site_visits (id INTEGER PRIMARY KEY AUTOINCREMENT, ip_address VARCHAR(45), visited_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
            try { $db->exec("ALTER TABLE site_visits ADD COLUMN country VARCHAR(100)"); } catch(Exception $e){}
            
            $db->exec("CREATE TABLE IF NOT EXISTS banned_ips (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address VARCHAR(45) UNIQUE NOT NULL,
                reason TEXT,
                banned_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            
            $db->exec("CREATE TABLE IF NOT EXISTS security_alerts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type VARCHAR(50) NOT NULL,
                ip_address VARCHAR(45),
                details TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        } else {
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
        }

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

        // Últimos pagos reportados
        $stmtPayments = $db->query("
            SELECT p.*, b.business_name, pl.name as plan_name, pl.duration_days 
            FROM payments p 
            JOIN businesses b ON p.tenant_id = b.id 
            JOIN plans pl ON p.plan_id = pl.id 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

        // Tráfico de últimos 7 días (para la gráfica)
        $daily_visits = [];
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $stmtVisits = $db->query("SELECT strftime('%Y-%m-%d', visited_at) as day, COUNT(*) as count FROM site_visits WHERE visited_at >= date('now', '-7 days') GROUP BY day ORDER BY day ASC");
        } else {
            $stmtVisits = $db->query("SELECT DATE(visited_at) as day, COUNT(*) as count FROM site_visits WHERE visited_at >= NOW() - INTERVAL '7 days' GROUP BY day ORDER BY day ASC");
        }
        $visits_data = $stmtVisits->fetchAll(PDO::FETCH_ASSOC);
        foreach($visits_data as $v) {
            $daily_visits[$v['day']] = $v['count'];
        }

        // Top Países
        $country_visits = $db->query("SELECT COALESCE(country, 'Desconocido') as country, COUNT(*) as count FROM site_visits GROUP BY country ORDER BY count DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('modules/superadmin/views/index', [
            'stats' => $stats,
            'payments' => $payments,
            'daily_visits' => $daily_visits,
            'country_visits' => $country_visits
        ]);
    }

    public function process_payment() {
        $this->requireSuperAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            
            $paymentId = $_POST['payment_id'] ?? 0;
            $action = $_POST['action'] ?? ''; // 'approve' or 'reject'

            try {
                $db->beginTransaction();

                $stmt = $db->prepare("SELECT * FROM payments WHERE id = ?");
                $stmt->execute([$paymentId]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$payment) throw new Exception("Pago no encontrado");

                if ($action === 'approve') {
                    // Update Payment
                    $db->prepare("UPDATE payments SET status = 'approved' WHERE id = ?")->execute([$paymentId]);

                    // Get Plan duration
                    $stmtPlan = $db->prepare("SELECT duration_days FROM plans WHERE id = ?");
                    $stmtPlan->execute([$payment['plan_id']]);
                    $days = $stmtPlan->fetchColumn() ?: 30;

                    // Update Tenant Subscription
                    // Convert days to interval (PostgreSQL) or modifier (SQLite)
                    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                        $sql = "UPDATE businesses SET subscription_status = 'active', plan_id = ?, trial_ends_at = datetime(COALESCE(trial_ends_at, 'now'), '+{$days} days') WHERE id = ?";
                    } else {
                        $sql = "UPDATE businesses SET subscription_status = 'active', plan_id = ?, trial_ends_at = COALESCE(trial_ends_at, CURRENT_TIMESTAMP) + INTERVAL '{$days} days' WHERE id = ?";
                    }
                    $db->prepare($sql)->execute([$payment['plan_id'], $payment['tenant_id']]);

                } else if ($action === 'reject') {
                    $db->prepare("UPDATE payments SET status = 'rejected' WHERE id = ?")->execute([$paymentId]);
                }

                $db->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    public function tenants() {
        $this->requireSuperAdmin();
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT b.*, 
                       (SELECT COUNT(*) FROM users u WHERE u.business_id = b.id) as subusers_count
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
        
        $this->view('modules/superadmin/views/users', ['users' => $users]);
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
            
            if ($id && $full_name && $username) {
                try {
                    $db->prepare("UPDATE users SET full_name = ?, username = ? WHERE id = ? AND role != 'super_admin'")->execute([$full_name, $username, $id]);
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
            $db = Database::getInstance()->getConnection();
            $id = $_POST['id'] ?? null;
            if ($id) {
                try {
                    // Prevenir eliminar administradores o super admins, o depender de FK? 
                    // El super_admin actual esta protegido por role != super_admin
                    $db->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'")->execute([$id]);
                    echo json_encode(['success' => true]);
                } catch(PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'No se puede eliminar el usuario porque tiene transacciones o pagos asociados en el sistema. Considere bloquearlo en su lugar.']);
                }
            }
            exit;
        }
    }

    public function finances() {
        $this->requireSuperAdmin();
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT p.*, b.business_name, pl.name as plan_name 
                FROM payments p 
                JOIN businesses b ON p.tenant_id = b.id 
                JOIN plans pl ON p.plan_id = pl.id 
                ORDER BY p.created_at DESC";
        $stmt = $db->query($sql);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->view('modules/superadmin/views/finances', [
            'payments' => $payments
        ]);
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
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $dbPath = DB_PATH;
            if (file_exists($dbPath)) {
                $filename = 'respaldo_tu_inventario_' . date('Y-m-d_H-i-s') . '.sqlite';
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($dbPath));
                readfile($dbPath);
                exit;
            } else {
                echo "Base de datos no encontrada localmente.";
            }
        } else {
            echo "El volcado de PostgreSQL/MySQL debe gestionarse externamente (pg_dump) por razones de seguridad en este servidor.";
        }
        exit;
    }

    public function payment_proof($id = 0) {
        $this->requireSuperAdmin();
        if (!$id) { header("HTTP/1.0 404 Not Found"); exit; }
        
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT proof_image FROM payments WHERE id = ?");
        $stmt->execute([$id]);
        $base64 = $stmt->fetchColumn();
        
        if ($base64 && strpos($base64, 'data:image') === 0) {
            list($type, $data) = explode(';', $base64);
            list(, $data)      = explode(',', $data);
            $imgData = base64_decode($data);
            $mime = str_replace('data:', '', $type);
            header("Content-Type: $mime");
            header('Cache-Control: public, max-age=86400');
            echo $imgData;
            exit;
        }
        
        header("HTTP/1.0 404 Not Found");
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
                // Registrar impersonación
                $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
                $db->prepare("INSERT INTO security_alerts (type, ip_address, details) VALUES ('IMPERSONATION', ?, ?)")
                   ->execute([$ip, "Superadmin impersonated user ID: " . $user['id'] . " (" . $user['username'] . ")"]);
                
                // Setear sesión
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
                }
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o restringido']);
            }
        }
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

