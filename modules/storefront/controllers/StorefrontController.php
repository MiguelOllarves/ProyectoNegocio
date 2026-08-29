<?php
class StorefrontController extends Controller {

    public function __construct() {
        // Auto-migración silenciosa para la tabla clients
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $newColumns = [
            'extra_phones' => 'TEXT',
            'workplace' => 'TEXT',
            'workplace_component' => 'TEXT',
            'workplace_detail' => 'TEXT',
            'workplace_address' => 'TEXT',
            'monthly_income' => 'TEXT', // O REAL
            'ip_address' => 'TEXT',
            'user_agent' => 'TEXT',
            'gps_location' => 'TEXT',
        ];
        foreach ($newColumns as $column => $type) {
            try { $db->exec("ALTER TABLE clients ADD COLUMN $column $type"); } catch (\Exception $e) {}
        }
    }

    /**
     * Panel de configuración de la tienda (requiere login)
     */
    public function index() {
        $businessId = $_SESSION['business_id'] ?? null;
        if (!$businessId) {
            echo "No tienes un negocio asociado.";
            return;
        }

        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();

        // Asegurar que existan las nuevas columnas
        try { $db->exec("ALTER TABLE store_config ADD COLUMN store_name TEXT"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE store_config ADD COLUMN background_image TEXT"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE store_config ADD COLUMN business_hours TEXT"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE store_config ADD COLUMN business_address TEXT"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE store_config ADD COLUMN contact_email TEXT"); } catch (\Exception $e) {}

        // Obtener config existente o defaults
        $stmt = $db->prepare("SELECT * FROM store_config WHERE business_id = :bid");
        $stmt->execute(['bid' => $businessId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        // Obtener datos del negocio
        $stmtBiz = $db->prepare("SELECT * FROM businesses WHERE id = :id");
        $stmtBiz->execute(['id' => $businessId]);
        $business = $stmtBiz->fetch(PDO::FETCH_ASSOC);

        $this->view('modules/storefront/views/config', [
            'config' => $config ?: [],
            'business' => $business
        ]);
    }

    /**
     * Guardar configuración de la tienda
     */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $businessId = $_SESSION['business_id'] ?? null;
        if (!$businessId) return;

        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();

        $store_name = trim($_POST['store_name'] ?? '');
        $hero_title = $_POST['hero_title'] ?? '';
        $hero_subtitle = $_POST['hero_subtitle'] ?? '';
        $primary_color = $_POST['primary_color'] ?? '#10b981';
        $whatsapp = $_POST['whatsapp'] ?? '';
        $instagram = $_POST['instagram'] ?? '';
        $facebook = $_POST['facebook'] ?? '';
        $tiktok = $_POST['tiktok'] ?? '';
        $twitter = $_POST['twitter'] ?? '';
        $show_prices = isset($_POST['show_prices']) ? 1 : 0;
        $is_published = isset($_POST['is_published']) ? 1 : 0;
        $slug_input = trim($_POST['slug'] ?? '');
        $business_hours = trim($_POST['business_hours'] ?? '');
        $business_address = trim($_POST['business_address'] ?? '');
        $contact_email = trim($_POST['contact_email'] ?? '');

        // Update Slug in businesses table
        $slug_error = false;
        if (!empty($slug_input)) {
            $newSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug_input), '-'));
            if (!empty($newSlug)) {
                $checkSlug = $db->prepare("SELECT id FROM businesses WHERE slug = :s AND id != :bid");
                $checkSlug->execute(['s' => $newSlug, 'bid' => $businessId]);
                if ($checkSlug->fetch()) {
                    $slug_error = true;
                } else {
                    $updateSlug = $db->prepare("UPDATE businesses SET slug = :s WHERE id = :bid");
                    $updateSlug->execute(['s' => $newSlug, 'bid' => $businessId]);
                    $_SESSION['business_slug'] = $newSlug;
                }
            }
        }

        $logo_url = $_POST['logo_url'] ?? '';
        if (!empty($_FILES['logo_image']['name']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['logo_image']['tmp_name'];
            $mime = mime_content_type($fileTmp);
            $size = filesize($fileTmp);
            
            if ($size > 1024 * 1024 * 3) {
                // Return Error (Too Large)
                header('X-Toast-Message: El logo excede el límite de 3MB');
                header('X-Toast-Type: error');
                http_response_code(400);
                exit;
            } elseif (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                header('X-Toast-Message: Formato de logo inválido (solo JPG, PNG, WEBP)');
                header('X-Toast-Type: error');
                http_response_code(400);
                exit;
            } else {
                $base64 = base64_encode(file_get_contents($fileTmp));
                $logo_url = 'data:' . $mime . ';base64,' . $base64;
            }
        }

        // Imagen de Fondo
        $background_image = $_POST['background_image_current'] ?? '';
        if (!empty($_FILES['background_image']['name']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['background_image']['tmp_name'];
            $mime = mime_content_type($fileTmp);
            $size = filesize($fileTmp);
            
            if ($size > 1024 * 1024 * 3) {
                header('X-Toast-Message: El fondo excede el límite de 3MB');
                header('X-Toast-Type: error');
                http_response_code(400);
                exit;
            } elseif (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
                header('X-Toast-Message: Formato de fondo inválido (solo JPG, PNG, WEBP)');
                header('X-Toast-Type: error');
                http_response_code(400);
                exit;
            } else {
                $base64 = base64_encode(file_get_contents($fileTmp));
                $background_image = 'data:' . $mime . ';base64,' . $base64;
            }
        }

        // Upsert: si ya existe, actualizar; si no, insertar
        $check = $db->prepare("SELECT id FROM store_config WHERE business_id = :bid");
        $check->execute(['bid' => $businessId]);

        if ($check->fetch()) {
            $sql = "UPDATE store_config SET store_name=:sn, hero_title=:ht, hero_subtitle=:hs, primary_color=:pc, logo_url=:lu, background_image=:bg, whatsapp=:wa, instagram=:ig, facebook=:fb, tiktok=:tk, twitter=:tw, show_prices=:sp, is_published=:ip, business_hours=:bh, business_address=:ba, contact_email=:ce, updated_at=CURRENT_TIMESTAMP WHERE business_id=:bid";
        } else {
            $sql = "INSERT INTO store_config (business_id, store_name, hero_title, hero_subtitle, primary_color, logo_url, background_image, whatsapp, instagram, facebook, tiktok, twitter, show_prices, is_published, business_hours, business_address, contact_email) VALUES (:bid, :sn, :ht, :hs, :pc, :lu, :bg, :wa, :ig, :fb, :tk, :tw, :sp, :ip, :bh, :ba, :ce)";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'bid' => $businessId,
            'sn'  => $store_name,
            'ht'  => $hero_title,
            'hs'  => $hero_subtitle,
            'pc'  => $primary_color,
            'lu'  => $logo_url,
            'bg'  => $background_image,
            'wa'  => $whatsapp,
            'ig'  => $instagram,
            'fb'  => $facebook,
            'tk'  => $tiktok,
            'tw'  => $twitter,
            'sp'  => $show_prices,
            'ip'  => $is_published,
            'bh'  => $business_hours,
            'ba'  => $business_address,
            'ce'  => $contact_email
        ]);

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($isAjax) {
            header('Content-Type: application/json');
            if ($slug_error) {
                echo json_encode(['success' => false, 'message' => 'La configuración se guardó, pero el Enlace Personalizado que elegiste ya está en uso.']);
            } else {
                $finalSlug = $_SESSION['business_slug'] ?? $businessId;
                echo json_encode(['success' => true, 'message' => 'Configuración guardada exitosamente.', 'slug' => $finalSlug]);
            }
            exit;
        }

        require_once __DIR__ . '/../../../core/FlashMessage.php';
        if ($slug_error) {
            FlashMessage::set('warning', 'La configuración se guardó, pero el Enlace Personalizado que elegiste ya está en uso.');
            header('Location: ' . BASE_URL . 'storefront');
        } else {
            FlashMessage::set('success', 'Configuración guardada exitosamente.');
            header('Location: ' . BASE_URL . 'storefront');
        }
        exit;
    }

    /**
     * Landing page PÚBLICA (sin autenticación)
     */
    public function show($identifier) {
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();

        // Datos del negocio
        if (is_numeric($identifier)) {
            $stmtBiz = $db->prepare("SELECT * FROM businesses WHERE id = :id");
            $stmtBiz->execute(['id' => $identifier]);
        } else {
            $stmtBiz = $db->prepare("SELECT * FROM businesses WHERE slug = :slug");
            $stmtBiz->execute(['slug' => $identifier]);
        }
        $business = $stmtBiz->fetch(PDO::FETCH_ASSOC);

        if (!$business) {
            http_response_code(404);
            echo "Tienda no encontrada.";
            return;
        }
        
        $businessId = $business['id'];

        // Config visual
        $stmtCfg = $db->prepare("SELECT * FROM store_config WHERE business_id = :bid");
        $stmtCfg->execute(['bid' => $businessId]);
        $config = $stmtCfg->fetch(PDO::FETCH_ASSOC) ?: [];

        // Verificar si está publicada
        if (empty($config) || !($config['is_published'] ?? 1)) {
            echo "<div style='text-align:center;margin-top:100px;font-family:sans-serif;'><h1>🔒 Esta tienda no está disponible</h1><p>El propietario no ha publicado su catálogo aún.</p></div>";
            return;
        }

        // Productos disponibles (stock > 0)
        $stmtProd = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.tenant_id = :tid AND (p.stock > 0 OR p.is_dish = TRUE) ORDER BY p.name ASC");
        $stmtProd->execute(['tid' => $businessId]);
        $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

        // Métodos de pago activos
        $isActiveTrue = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 'TRUE' : '1';
        $paymentMethods = [];
        try {
            $stmtPay = $db->prepare("SELECT * FROM payment_methods WHERE is_active = $isActiveTrue AND (tenant_id = :tid OR tenant_id IS NULL)");
            $stmtPay->execute(['tid' => $businessId]);
            $paymentMethods = $stmtPay->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $stmtPay = $db->prepare("SELECT * FROM payment_methods WHERE is_active = $isActiveTrue");
            $stmtPay->execute();
            $paymentMethods = $stmtPay->fetchAll(PDO::FETCH_ASSOC);
        }

        require_once __DIR__ . '/../../../core/Settings.php';
        $bcvRate = Settings::getBcvRate();

        $stmtWp = $db->prepare("SELECT DISTINCT workplace FROM clients WHERE tenant_id = :tid AND workplace IS NOT NULL AND workplace != '' ORDER BY workplace ASC");
        $stmtWp->execute(['tid' => $businessId]);
        $workplaces = $stmtWp->fetchAll(PDO::FETCH_COLUMN);

        $this->view('modules/storefront/views/landing', [
            'business' => $business,
            'config' => $config,
            'products' => $products,
            'paymentMethods' => $paymentMethods,
            'bcvRate' => $bcvRate,
            'workplaces' => $workplaces
        ]);
    }

    /**
     * API: Registra un cliente desde el formulario de solicitud de crédito del storefront
     */
    public function registerClient() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['business_id']) || empty($data['document'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            return;
        }

        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $tid = $data['business_id'];
        $document = trim($data['document']);
        $phone = trim($data['phone']);
        
        // Verificar si ya existe por cédula
        $stmtCheck = $db->prepare("SELECT id FROM clients WHERE tenant_id = :tid AND document = :doc");
        $stmtCheck->execute(['tid' => $tid, 'doc' => $document]);
        if ($stmtCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Ya existe un cliente registrado con esta Cédula en esta tienda.']);
            return;
        }

        // Obtener IP
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
        // Formatear GPS
        $gpsLocation = (isset($data['gpsLat']) && isset($data['gpsLng'])) 
            ? json_encode(['lat' => $data['gpsLat'], 'lng' => $data['gpsLng']]) 
            : null;

        $sql = "INSERT INTO clients (
                    tenant_id, name, email, document, phone, extra_phones, address, 
                    workplace, workplace_component, workplace_detail, workplace_address, monthly_income, 
                    ip_address, user_agent, gps_location
                ) VALUES (
                    :tid, :name, :email, :doc, :phone, :extraphone, :address,
                    :workplace, :wpcomponent, :wpdetail, :wpaddress, :income,
                    :ip, :ua, :gps
                )";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'tid' => $tid,
                'name' => trim($data['name'] ?? ''),
                'email' => trim($data['email'] ?? ''),
                'doc' => $document,
                'phone' => $phone,
                'extraphone' => '',
                'address' => trim($data['address'] ?? ''),
                'workplace' => (!empty(trim($data['customWorkplace'] ?? ''))) ? trim($data['customWorkplace']) : trim($data['workplace'] ?? ''),
                'wpcomponent' => trim($data['workplaceComponent'] ?? ''),
                'wpdetail' => trim($data['workplaceDetail'] ?? ''),
                'wpaddress' => trim($data['workplaceAddress'] ?? ''),
                'income' => trim($data['monthlyIncome'] ?? ''),
                'ip' => $ipAddress,
                'ua' => trim($data['userAgent'] ?? ''),
                'gps' => $gpsLocation
            ]);
            
            // Enviar notificación al administrador
            try {
                require_once __DIR__ . '/../../credits/models/Notification.php';
                $clientId = $db->lastInsertId();
                $sessionWasNull = !isset($_SESSION['business_id']);
                if ($sessionWasNull) $_SESSION['business_id'] = $tid;
                
                $msg = "El cliente " . trim($data['name'] ?? 'Público') . " requiere revisión para aprobar su solicitud de crédito mediante la tienda web.";
                Notification::send('client_registration', 'Nueva Solicitud Cliente', $msg, 'administrador', 'client', $clientId);
                
                if (!empty($data['email'])) {
                    require_once __DIR__ . '/../../../core/Mailer.php';
                    require_once __DIR__ . '/../../../core/Settings.php';
                    // We also need the business name since tenant isolation applies
                    $stmtBizName = $db->prepare("SELECT business_name FROM businesses WHERE id = ?");
                    $stmtBizName->execute([$tid]);
                    $bizName = $stmtBizName->fetchColumn() ?: 'la Tienda';
                    
                    $clientName = trim($data['name']);
                    Mailer::send($data['email'], 'Solicitud de Crédito Recibida', "Hola $clientName, hemos recibido tu solicitud de cliente en $bizName. El administrador la revisará pronto.");
                }
                
                if ($sessionWasNull) unset($_SESSION['business_id']);
            } catch (\Exception $e) {}
            
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos: ' . $e->getMessage()]);
        }
    }

    /**
     * API: Procesa el carrito de compras desde el storefront.
     */
    public function checkout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['business_id']) || empty($data['items'])) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            return;
        }

        require_once __DIR__ . '/../../../config/Database.php';
        require_once __DIR__ . '/../../credits/models/Notification.php';
        require_once __DIR__ . '/../../credits/models/Credit.php';
        
        $db = Database::getInstance()->getConnection();
        
        $sessionWasNull = !isset($_SESSION['business_id']);
        if ($sessionWasNull) {
            $_SESSION['business_id'] = $data['business_id'];
        }
        
        $paymentMethod = $data['payment_method'] ?? '';
        $clientId = null;
        
        // Validación Especial para "Crédito (Fiado)"
        if ($paymentMethod === 'Crédito (Fiado)') {
            $customerPhone = $data['customer_phone'] ?? '';
            $customerDocument = $data['customer_document'] ?? '';
            
            if (empty($customerDocument)) {
                if ($sessionWasNull) unset($_SESSION['business_id']);
                echo json_encode(['success' => false, 'message' => 'Para comprar a crédito es obligatorio ingresar tu Cédula de Identidad en los datos de entrega.']);
                return;
            }

            // Buscar cliente por cédula y teléfono
            $cleanPhone = trim($customerPhone);
            $cleanDoc = trim($customerDocument);
            
            $stmtClient = $db->prepare("SELECT id FROM clients WHERE tenant_id = :tid AND document = :doc AND (phone = :phone OR phone LIKE :phone_like OR extra_phones LIKE :phone_like)");
            $stmtClient->execute([
                'tid' => $data['business_id'],
                'doc' => $cleanDoc,
                'phone' => $cleanPhone,
                'phone_like' => '%' . $cleanPhone . '%'
            ]);
            $client = $stmtClient->fetch(PDO::FETCH_ASSOC);
            
            if (!$client) {
                if ($sessionWasNull) unset($_SESSION['business_id']);
                echo json_encode(['success' => false, 'message' => 'Para comprar a crédito, debes estar registrado como cliente de confianza con esa Cédula y Número de Teléfono exactos. Si no estás registrado, solicita el crédito en el botón superior.']);
                return;
            }
            
            $clientId = $client['id'];
        }
        
        // [SECURITY FIX] Recalcular total real desde la fuente de verdad en BD
        $realTotalUsd = 0;
        $finalItems = [];
        require_once __DIR__ . '/../../inventory/models/Product.php';
        $productModel = new Product();
        
        foreach ($data['items'] as $item) {
            $prod = $productModel->find($item['id'] ?? 0);
            if ($prod) {
                // Si el stock está en BD, validar que esté disponible
                $qty = (float)($item['qty'] ?? 1);
                $price = (float)$prod['price'];
                $realTotalUsd += ($price * $qty);
                $item['price'] = $price; // Override fake frontend prices
                $finalItems[] = $item;
            }
        }
        $data['items'] = $finalItems;
        $data['total_usd'] = $realTotalUsd;
        
        // Re-calcular el equivalente en BS usando la tasa BCV real del server
        require_once __DIR__ . '/../../../core/Settings.php';
        $bcvRate = (float)Settings::getBcvRate();
        $realTotalBs = $realTotalUsd * $bcvRate;
        $data['total_bs'] = $realTotalBs;

        $sql = "INSERT INTO store_orders (tenant_id, customer_name, customer_phone, customer_address, notes, payment_method, total_usd, total_bs, items_json) 
                VALUES (:tid, :cname, :cphone, :caddr, :notes, :pmethod, :tusd, :tbs, :items)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'tid' => $data['business_id'],
            'cname' => $data['customer_name'] ?? '',
            'cphone' => $data['customer_phone'] ?? '',
            'caddr' => $data['customer_address'] ?? '',
            'notes' => $data['notes'] ?? '',
            'pmethod' => $paymentMethod,
            'tusd' => $data['total_usd'] ?? 0,
            'tbs' => $data['total_bs'] ?? 0,
            'items' => json_encode($data['items'])
        ]);
        
        $orderId = $db->lastInsertId();
        
        // Si es a crédito, registramos la deuda automáticamente
        if ($paymentMethod === 'Crédito (Fiado)' && $clientId) {
            $totalUsd = (float)($data['total_usd'] ?? 0);
            $creditModel = new Credit();
            
            $creditNotes = "Pedido online #" . $orderId . ". Artículos: ";
            foreach ($data['items'] as $item) {
                $creditNotes .= $item['qty'] . "x " . $item['name'] . ", ";
            }
            $creditNotes = rtrim($creditNotes, ", ");
            
            $creditModel->create([
                'client_id' => $clientId,
                'sale_id' => null, // Opcional, si existiera una vinculación directa
                'credit_type' => 'producto',
                'interest_rate' => 0,
                'down_payment' => 0,
                'base_amount' => $totalUsd,
                'total_amount' => $totalUsd,
                'remaining_amount' => $totalUsd,
                'due_date' => date('Y-m-d', strtotime('+15 days')), // Por defecto 15 días
                'notes' => $creditNotes,
                'status' => 'activo'
            ]);
        }

        // Enviar notificación al administrador
        try {
            $msg = "Nuevo pedido de " . ($data['customer_name'] ?? 'Cliente') . " por $" . number_format($data['total_usd'] ?? 0, 2);
            if ($paymentMethod === 'Crédito (Fiado)') $msg .= " (A CRÉDITO)";
            Notification::send('order', 'Nuevo Pedido Tienda', $msg, 'administrador', 'store_order', $orderId);
            
            // Enviar Correo Electrónico
            $stmtAdmin = $db->prepare("SELECT email FROM businesses WHERE id = ?");
            $stmtAdmin->execute([$data['business_id']]);
            $adminData = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
            
            if ($adminData && !empty($adminData['email'])) {
                require_once __DIR__ . '/../../../core/Mailer.php';
                require_once __DIR__ . '/../../../core/EmailTemplates.php';
                
                $loginUrl = BASE_URL . "dashboard/pedidos";
                $emailHtml = EmailTemplates::getStoreOrderEmail(
                    $data['customer_name'] ?? 'Cliente No Identificado',
                    $data['customer_phone'] ?? 'No especificado',
                    $data['total_usd'] ?? 0,
                    $data['notes'] ?? 'Ninguna',
                    $paymentMethod,
                    $loginUrl
                );
                
                Mailer::send($adminData['email'], "Nuevo Pedido Recibido - #" . $orderId, $emailHtml);
            }
            
        } catch (\Exception $e) {
            error_log("Error enviando notificación de pedido: " . $e->getMessage());
        }

        if ($sessionWasNull) {
            unset($_SESSION['business_id']);
        }

        echo json_encode(['success' => true, 'order_id' => $orderId]);
    }

    /**
     * Admin: Vista de Pedidos de Tienda
     */
    public function orders() {
        $businessId = $_SESSION['business_id'] ?? null;
        if (!$businessId) return;

        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 5;
        $offset = ($page - 1) * $limit;

        $stmtCount = $db->prepare("SELECT COUNT(*) FROM store_orders WHERE tenant_id = :tid");
        $stmtCount->execute(['tid' => $businessId]);
        $totalOrders = $stmtCount->fetchColumn();

        $totalPages = ceil($totalOrders / $limit);
        if ($totalPages == 0) $totalPages = 1;

        $stmt = $db->prepare("SELECT * FROM store_orders WHERE tenant_id = :tid ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset);
        $stmt->execute(['tid' => $businessId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('modules/storefront/views/orders', [
            'orders' => $orders,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'totalRecords' => $totalOrders
        ]);
    }

    /**
     * Admin: Actualizar estado de pedido
     */
    public function updateOrderStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
        
        $orderId = $_POST['order_id'] ?? null;
        $status = $_POST['status'] ?? null;
        $businessId = $_SESSION['business_id'] ?? null;

        if (!$orderId || !$status || !$businessId) return;

        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("UPDATE store_orders SET status = :st WHERE id = :id AND tenant_id = :tid");
        $stmt->execute([
            'st' => $status,
            'id' => $orderId,
            'tid' => $businessId
        ]);
        
        header('Location: ' . BASE_URL . 'storefront/orders');
    }
}
