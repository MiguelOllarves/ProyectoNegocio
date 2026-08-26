<?php
class AuthController extends Controller {
    public function index() {
        // Redirigir al dashboard preventivamente sin loops
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }
        header('Location: ' . BASE_URL . '?login=1');
        exit;
    }
    
    public function register() {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit;
        }
        $this->view('modules/users/views/register');
    }
    
    public function process_register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Middleware::checkRateLimit('register', 3, 15)) {
                $this->view('modules/users/views/register', ['error' => 'Demasiados intentos de registro. Por favor, espera 15 minutos.']);
                return;
            }
            $owner_name = $_POST['owner_name'] ?? '';
            $document_id = $_POST['document_id'] ?? '';
            $owner_phone = $_POST['owner_phone'] ?? '';
            
            $business_name = $_POST['business_name'] ?? '';
            $category = $_POST['category'] ?? '';
            $rif = $_POST['rif'] ?? '';
            $business_phone = $_POST['business_phone'] ?? '';
            
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($email) || empty($password) || empty($business_name)) {
                $this->view('modules/users/views/register', ['error' => 'Faltan campos obligatorios.']);
                return;
            }
            if ($password !== $confirm_password) {
                $this->view('modules/users/views/register', ['error' => 'Las contraseñas no coinciden.']);
                return;
            }
            
            $passwordCheck = Middleware::validatePasswordComplexity($password);
            if ($passwordCheck !== true) {
                $this->view('modules/users/views/register', ['error' => $passwordCheck]);
                return;
            }
            
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            
            // Verificar que la cédula no exista
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = :usr");
            $stmtCheck->execute(['usr' => $document_id]);
            if ($stmtCheck->fetch()) {
                $this->view('modules/users/views/register', ['error' => 'Esta cédula ya se encuentra registrada en el sistema.']);
                return;
            }
            
            try {
                $db->beginTransaction();
                
                // 1. Guardar o Negocio (Inquilino)
                // Generar slug del negocio
                $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $business_name), '-'));
                if (empty($baseSlug)) $baseSlug = 'tienda-' . time();
                
                $slug = $baseSlug;
                $counter = 1;
                $stmtSlugCheck = $db->prepare("SELECT id FROM businesses WHERE slug = ?");
                while (true) {
                    $stmtSlugCheck->execute([$slug]);
                    if (!$stmtSlugCheck->fetch()) break; // Disponible
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $sqlBusiness = "INSERT INTO businesses (owner_name, business_name, rif, owner_phone, business_phone, document_id, email, category, slug)
                                VALUES (:on, :bn, :rif, :op, :bp, :doc, :email, :cat, :slug)";
                $stmtBusiness = $db->prepare($sqlBusiness);
                $stmtBusiness->execute([
                    'on' => $owner_name,
                    'bn' => $business_name,
                    'rif' => $rif,
                    'op' => $owner_phone,
                    'bp' => $business_phone,
                    'doc' => $document_id,
                    'email' => $email,
                    'cat' => $category,
                    'slug' => $slug
                ]);
                $business_id = $db->lastInsertId();
                
                // 2. Guardar el Usuario Administrador (Dueño del tenant)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sqlUser = "INSERT INTO users (business_id, username, full_name, password, role, status)
                            VALUES (:bid, :usr, :fn, :pass, 'administrador', 1)";
                $stmtUser = $db->prepare($sqlUser);
                $stmtUser->execute([
                    'bid' => $business_id,
                    'usr' => $document_id, // Se usa la cédula como usuario nativo del sistema
                    'fn' => $owner_name,
                    'pass' => $hashed_password
                ]);
                
                $db->commit();
                
                // 3. Ejecutar Seeder dinámico según categoría
                $this->seedBusinessData($db, $business_id, $category);
                
                // 4. Enviar correo de Bienvenida
                require_once __DIR__ . '/../../../core/Mailer.php';
                $login_url = BASE_URL . "auth";
                $store_url = BASE_URL . "tienda/{$slug}";
                require_once __DIR__ . '/../../../core/EmailTemplates.php';
                $emailBody = EmailTemplates::getWelcomeEmail($owner_name, $business_name, $login_url, $store_url);
                Mailer::send($email, "¡Bienvenido a Tu Inventario - {$business_name}!", $emailBody);
                
                // Enviar notificación Push al super_admin
                try {
                    require_once __DIR__ . '/../../credits/models/Notification.php';
                    Notification::send(
                        'nuevo_registro',
                        '🚀 Nuevo Negocio Registrado',
                        "El negocio '{$business_name}' acaba de registrarse.",
                        'super_admin',
                        'business_registration',
                        $business_id
                    );
                } catch (\Exception $e) {
                    error_log("Error enviando push de registro: " . $e->getMessage());
                }
                
                // Registro Exitoso
                Middleware::resetRateLimit('register');
                $this->view('modules/users/views/register', ['success' => '¡Tu espacio de trabajo ha sido provisionado exitosamente!']);
            } catch (Exception $e) {
                $db->rollBack();
                $this->view('modules/users/views/register', ['error' => 'Ocurrió un error en la base de datos: ' . $e->getMessage()]);
            }
        }
    }
    
    private function seedBusinessData($db, $business_id, $category) {
        $seeds = [
            'gastronomia' => [
                'categories' => ['Bebidas', 'Entradas', 'Platos Principales', 'Postres'],
                'products' => [
                    ['name' => 'Refresco 2L', 'price' => 2.50, 'cat' => 'Bebidas'],
                    ['name' => 'Hamburguesa Clásica', 'price' => 5.00, 'cat' => 'Platos Principales'],
                ]
            ],
            'viveres' => [
                'categories' => ['Harinas', 'Granos', 'Lácteos', 'Enlatados', 'Aseo Personal'],
                'products' => [
                    ['name' => 'Harina PAN', 'price' => 1.20, 'cat' => 'Harinas'],
                    ['name' => 'Arroz Mary 1Kg', 'price' => 1.30, 'cat' => 'Granos'],
                ]
            ],
            'repuestos' => [
                'categories' => ['Frenos', 'Suspensión', 'Motor', 'Eléctricos', 'Lubricantes'],
                'products' => [
                    ['name' => 'Pastillas de Freno Universales', 'price' => 15.00, 'cat' => 'Frenos'],
                    ['name' => 'Aceite Mineral 20W50', 'price' => 8.50, 'cat' => 'Lubricantes'],
                ]
            ],
            'vehiculos' => [
                'categories' => ['Motos', 'Carros Usados', 'Accesorios'],
                'products' => [
                    ['name' => 'Casco Integral', 'price' => 45.00, 'cat' => 'Accesorios'],
                ]
            ],
            'bienes_raices' => [
                'categories' => ['Alquiler', 'Venta', 'Trámites'],
                'products' => [
                    ['name' => 'Honorarios Contrato Alquiler', 'price' => 50.00, 'cat' => 'Trámites'],
                ]
            ],
            'tecnologia' => [
                'categories' => ['Smartphones', 'Laptops', 'Accesorios', 'Servicio Técnico'],
                'products' => [
                    ['name' => 'Forro Protector Silicone', 'price' => 5.00, 'cat' => 'Accesorios'],
                    ['name' => 'Cable USB-C de Carga Rápida', 'price' => 8.00, 'cat' => 'Accesorios'],
                ]
            ],
            'general' => [
                'categories' => ['General', 'Servicios'],
                'products' => [
                    ['name' => 'Producto Base', 'price' => 10.00, 'cat' => 'General'],
                ]
            ]
        ];

        $seed = $seeds[$category] ?? $seeds['general'];
        $catMap = [];

        try {
            $db->beginTransaction();
            // Insert categories
            $stmtCat = $db->prepare("INSERT INTO categories (tenant_id, name) VALUES (?, ?)");
            foreach ($seed['categories'] as $catName) {
                $stmtCat->execute([$business_id, $catName]);
                $catMap[$catName] = $db->lastInsertId();
            }

            // Insert default products
            $stmtProd = $db->prepare("INSERT INTO products (tenant_id, category_id, name, price, stock) VALUES (?, ?, ?, ?, ?)");
            foreach ($seed['products'] as $prod) {
                $catId = $catMap[$prod['cat']] ?? null;
                $stmtProd->execute([$business_id, $catId, $prod['name'], $prod['price'], 10]);
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            // Ignore seeding errors so it doesn't break registration
            error_log("Error in Seeder: " . $e->getMessage());
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            if (!Middleware::checkRateLimit('login', 5, 15)) {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => 'Demasiados intentos fallidos. Por favor, espera 15 minutos.']);
                    exit;
                } else {
                    $_SESSION['login_error'] = 'Demasiados intentos fallidos. Por favor, espera 15 minutos.';
                    header('Location: ' . BASE_URL . '?login=1');
                    exit;
                }
            }

            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            require_once __DIR__ . '/../../../config/Database.php';
            $db = Database::getInstance()->getConnection();
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            try {
                
                $stmt = $db->prepare("SELECT u.*, b.category as business_category, b.slug as business_slug 
                                      FROM users u 
                                      LEFT JOIN businesses b ON u.business_id = b.id 
                                      WHERE u.username = :usr 
                                         OR (u.role = 'administrador' AND b.document_id = :usr2)
                                      LIMIT 1");
                $stmt->execute(['usr' => $username, 'usr2' => $username]);
                $user = $stmt->fetch();
            } catch (Exception $e) {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => 'Error interno de BD: ' . $e->getMessage()]);
                    exit;
                } else {
                    $_SESSION['login_error'] = 'Error interno de BD: ' . $e->getMessage();
                    header('Location: ' . BASE_URL . '?login=1');
                    exit;
                }
            }
            
            // Usando verify de Bcrypt (el hash se creo con password_hash de PHP)
            if ($user && password_verify($password, $user['password'])) {
                try {
                    // Asegurar que exista la columna active_session_id
                    $db->exec("ALTER TABLE users ADD COLUMN active_session_id VARCHAR(255)");
                } catch(Exception $e) {}

                // CHECK SINGLE SESSION LOGIC
                $forceClose = !empty($_POST['force_close']);
                if (!empty($user['active_session_id']) && $user['active_session_id'] !== session_id() && !$forceClose) {
                    // Check if this session still actually exists in the sessions table
                    $stmtCheckSession = $db->prepare("SELECT id FROM sessions WHERE id = ?");
                    $stmtCheckSession->execute([$user['active_session_id']]);
                    if ($stmtCheckSession->fetch()) {
                        // The other session is alive. Fetch info about it.
                        $otherDevice = "otro dispositivo";
                        try {
                            $stmtDevice = $db->prepare("SELECT os_name, browser_name FROM login_sessions WHERE user_id = ? ORDER BY logged_in_at DESC LIMIT 1");
                            $stmtDevice->execute([$user['id']]);
                            if ($d = $stmtDevice->fetch()) {
                                $otherDevice = "<b>{$d['os_name']} / {$d['browser_name']}</b>";
                            }
                        } catch(Exception $e) {}
                        
                        $errMsg = "Tienes una sesión abierta en {$otherDevice}.<br><br>Por favor, ciérrala allá para poder ingresar aquí, o fuerza el cierre.";
                        if ($isAjax) {
                            echo json_encode(['success' => false, 'message' => $errMsg, 'requires_force_close' => true]);
                            exit;
                        } else {
                            $_SESSION['login_error'] = $errMsg;
                            header('Location: ' . BASE_URL . '?login=1');
                            exit;
                        }
                    }
                }

                if ($forceClose && !empty($user['active_session_id'])) {
                    // Destruir físicamente la otra sesión de la base de datos de PHP sessions
                    $stmtKill = $db->prepare("DELETE FROM sessions WHERE id = ?");
                    $stmtKill->execute([$user['active_session_id']]);
                }

                // --- UPDATE NEW SESSION ID IN USERS ---
                $stmtUpdateSession = $db->prepare("UPDATE users SET active_session_id = ? WHERE id = ?");
                $stmtUpdateSession->execute([session_id(), $user['id']]);

                Middleware::resetRateLimit('login');
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['business_id'] = $user['business_id'] ?? null;
                $_SESSION['business_slug'] = $user['business_slug'] ?? $user['business_id'];
                
                // Cargar permisos a la sesión
                try {
                    $_SESSION['permissions'] = json_decode($user['permissions_json'] ?? '[]', true);
                    if (!is_array($_SESSION['permissions'])) $_SESSION['permissions'] = [];
                } catch(Exception $e) {
                    $_SESSION['permissions'] = [];
                }
                
                // --- SESSION TRACKING: Historial completo de conexiones ---
                $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
                $device = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $fingerprint = $_POST['fingerprint'] ?? '';
                $geolocation = $_POST['geolocation'] ?? '';
                
                try {
                    // Auto-crear tabla login_sessions si no existe
                    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                        $db->exec("CREATE TABLE IF NOT EXISTS login_sessions (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            user_id INTEGER NOT NULL,
                            ip_address VARCHAR(45),
                            user_agent TEXT,
                            device_type VARCHAR(20),
                            os_name VARCHAR(50),
                            browser_name VARCHAR(50),
                            location VARCHAR(255),
                            fingerprint VARCHAR(255),
                            logged_in_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                        )");
                    } else {
                        $db->exec("CREATE TABLE IF NOT EXISTS login_sessions (
                            id SERIAL PRIMARY KEY,
                            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                            ip_address VARCHAR(45),
                            user_agent TEXT,
                            device_type VARCHAR(20),
                            os_name VARCHAR(50),
                            browser_name VARCHAR(50),
                            location VARCHAR(255),
                            fingerprint VARCHAR(255),
                            logged_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )");
                    }
                    
                    // Detectar tipo de dispositivo, OS y navegador
                    $deviceType = 'Escritorio';
                    if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod/i', $device)) $deviceType = 'Teléfono';
                    elseif (preg_match('/iPad|Android(?!.*Mobile)|Tablet/i', $device)) $deviceType = 'Tablet';
                    
                    $osName = 'Desconocido';
                    if (stripos($device, 'Windows NT 10') !== false || stripos($device, 'Windows NT 11') !== false) $osName = 'Windows';
                    elseif (stripos($device, 'Windows') !== false) $osName = 'Windows';
                    elseif (stripos($device, 'Macintosh') !== false || stripos($device, 'Mac OS') !== false) $osName = 'Mac OS';
                    elseif (stripos($device, 'Android') !== false) $osName = 'Android';
                    elseif (stripos($device, 'iPhone') !== false || stripos($device, 'iPad') !== false) $osName = 'iOS';
                    elseif (stripos($device, 'Linux') !== false) $osName = 'Linux';
                    
                    $browserName = 'Desconocido';
                    if (stripos($device, 'Edg') !== false) $browserName = 'Microsoft Edge';
                    elseif (stripos($device, 'OPR') !== false || stripos($device, 'Opera') !== false) $browserName = 'Opera';
                    elseif (stripos($device, 'Chrome') !== false) $browserName = 'Google Chrome';
                    elseif (stripos($device, 'Firefox') !== false) $browserName = 'Mozilla Firefox';
                    elseif (stripos($device, 'Safari') !== false) $browserName = 'Safari';
                    
                    // Insertar registro de sesión
                    $stmtSession = $db->prepare("INSERT INTO login_sessions (user_id, ip_address, user_agent, device_type, os_name, browser_name, location, fingerprint) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtSession->execute([$user['id'], $ip, $device, $deviceType, $osName, $browserName, $geolocation, $fingerprint]);
                    
                    // También actualizar la última conexión en users (para compatibilidad)
                    try {
                        $db->exec("ALTER TABLE users ADD COLUMN last_ip VARCHAR(45)");
                        $db->exec("ALTER TABLE users ADD COLUMN last_device VARCHAR(255)");
                        $db->exec("ALTER TABLE users ADD COLUMN last_location VARCHAR(255)");
                    } catch(Exception $ignore) {}
                    
                    $stmtTrack = $db->prepare("UPDATE users SET last_ip = ?, last_device = ?, last_location = ? WHERE id = ?");
                    $stmtTrack->execute([$ip, $device, $geolocation, $user['id']]);
                    
                } catch(Exception $ex) {
                    // Silenciar errores de tracking para no romper el login
                }
                // ---------------------------------

                if ($isAjax) {
                    $redir = ($user['role'] === 'super_admin') ? BASE_URL . 'superadmin' : BASE_URL . 'dashboard';
                    echo json_encode(['success' => true, 'redirect' => $redir]);
                    exit;
                }
                
                $redir = ($user['role'] === 'super_admin') ? BASE_URL . 'superadmin' : BASE_URL . 'dashboard';
                header('Location: ' . $redir);
                exit;
            } else {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => 'Credenciales inválidas. Verifica tu usuario y contraseña.']);
                    exit;
                }
                $_SESSION['login_error'] = 'Credenciales inválidas. Intente de nuevo.';
                header('Location: ' . BASE_URL . '?login=1');
                exit;
            }
        }
    }
    
    public function logout() {
        if (!empty($_SESSION['user_id'])) {
            require_once __DIR__ . '/../../../config/Database.php';
            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("UPDATE users SET active_session_id = NULL WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
            } catch (Exception $e) {}
        }
        session_destroy();

        // Responder en caso de ser un request silencioso o beacon (al cerrar pestaña)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
            echo json_encode(['success' => true]);
            exit;
        }

        header('Location: ' . BASE_URL);
        exit;
    }

    public function fixpostgres() {
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        try {
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
                die("Este parche solo aplica para la base de datos PostgreSQL de Vercel.");
            }
            
            $db->exec("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            $db->exec("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('super_admin', 'administrador', 'empleado', 'vendedor'))");
            
            $sql = 'INSERT INTO users (business_id, username, full_name, password, role, status) 
                    VALUES (NULL, \'superadmin\', \'Desarrollador / Sistema\', \'$2y$10$uNsBBWuU8WbvVEWXUFhw4uhzFxChRa937Mg/HuLlrmzFIIkrgQIPK\', \'super_admin\', 1) 
                    ON CONFLICT (username) DO NOTHING';
            $db->exec($sql);
            
            echo "<h2>¡Parche Aplicado con Éxito en la Base de Datos de Supabase!</h2>";
            echo "<p>El usuario <b>superadmin</b> con clave <b>123456</b> ahora tiene permiso para entrar.</p>";
            echo "<a href='" . BASE_URL . "auth' style='display:inline-block; padding:10px 20px; background:#10b981; color:white; text-decoration:none; border-radius:5px;'>Ir Iniciar Sesión</a>";
        } catch (Exception $e) {
            echo "<h2>Error aplicando parche:</h2><p>" . $e->getMessage() . "</p>";
        }
    }
}
