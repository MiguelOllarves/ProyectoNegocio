<?php
require_once __DIR__ . '/../models/Credit.php';
require_once __DIR__ . '/../models/CreditPayment.php';
require_once __DIR__ . '/../models/Notification.php';

class CreditsController extends Controller {
    private $creditModel;
    private $paymentModel;

    public function __construct() {
        $this->creditModel = new Credit();
        $this->paymentModel = new CreditPayment();
        
        // Auto-migración silenciosa
        try {
            $db = Database::getInstance()->getConnection();
            $db->exec("ALTER TABLE credits ADD COLUMN credit_type TEXT DEFAULT 'producto'");
            $db->exec("ALTER TABLE credits ADD COLUMN interest_rate REAL DEFAULT 0");
            $db->exec("ALTER TABLE credits ADD COLUMN down_payment REAL DEFAULT 0");
            $db->exec("ALTER TABLE credits ADD COLUMN base_amount REAL DEFAULT 0");
            $db->exec("UPDATE credits SET base_amount = total_amount WHERE base_amount = 0 OR base_amount IS NULL");
        } catch (\Exception $e) {}
    }

    /**
     * Vista principal: Directorio de créditos con filtros.
     * Al cargar, ejecuta la verificación de créditos vencidos automáticamente.
     */
    public function index() {
        // === MOTOR DE ALERTAS: Se ejecuta en cada carga de la vista principal ===
        $alertResult = $this->creditModel->checkOverdueCredits();
        
        $this->view('modules/credits/views/index', [
            'creditAlerts' => $alertResult['alerts'],
            'updatedCount' => $alertResult['updated'],
        ]);
    }

    /**
     * Lista HTMX: Carga dinámica de créditos con datos de cliente.
     */
    public function list() {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $filter = $_GET['status'] ?? null;
        $credits = $this->creditModel->allWithClients($filter);

        if (empty($credits)) {
            echo "<tr><td colspan='6' class='p-10 text-center text-gray-400 dark:text-gray-500'>
                    <i class='fas fa-hand-holding-usd text-4xl mb-3 block opacity-30'></i>
                    No hay créditos registrados.
                  </td></tr>";
            return;
        }

        $today = new DateTime('today');

        foreach ($credits as $c) {
            $statusBadge = $this->getStatusBadge($c['status']);
            $remaining = number_format($c['remaining_amount'], 2);
            $total = number_format($c['total_amount'], 2);
            $paid = number_format($c['paid_amount'], 2);
            $clientName = htmlspecialchars($c['client_name']);
            $clientPhone = htmlspecialchars($c['client_phone'] ?? '');
            $dueDate = $c['due_date'] ? date('d/m/Y', strtotime($c['due_date'])) : '—';

            // Porcentaje pagado para la barra de progreso
            $pct = $c['total_amount'] > 0 ? min(100, round(($c['paid_amount'] / $c['total_amount']) * 100)) : 0;

            // Calcular urgencia para indicador visual
            $urgencyClass = '';
            $urgencyIcon = '';
            if ($c['due_date'] && $c['status'] !== 'pagado') {
                $dueDateObj = new DateTime($c['due_date']);
                $dueDateObj->setTime(0, 0, 0);
                $interval = $today->diff($dueDateObj);
                $diffDays = (int) $interval->format('%r%a');
                
                if ($diffDays < 0) {
                    $urgencyClass = 'border-l-4 border-red-500 bg-red-50/50 dark:bg-red-900/10';
                    $daysOverdue = abs($diffDays);
                    $urgencyIcon = "<span class='inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 ml-1'><i class='fas fa-fire text-[8px]'></i>{$daysOverdue}d atraso</span>";
                } elseif ($diffDays === 0) {
                    $urgencyClass = 'border-l-4 border-amber-500 bg-amber-50/50 dark:bg-amber-900/10';
                    $urgencyIcon = "<span class='inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 ml-1'><i class='fas fa-exclamation-circle text-[8px]'></i>HOY</span>";
                } elseif ($diffDays === 1) {
                    $urgencyClass = 'border-l-4 border-yellow-400 bg-yellow-50/30 dark:bg-yellow-900/10';
                    $urgencyIcon = "<span class='inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400 ml-1'><i class='fas fa-clock text-[8px]'></i>MAÑANA</span>";
                }
            }

            echo "
            <tr class='hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors {$urgencyClass}'>
                <td class='p-4'>
                    <div class='flex items-center gap-3'>
                        <div class='w-9 h-9 bg-gradient-to-br from-brand-400 to-accent-400 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow-sm'>
                            " . strtoupper(substr($c['client_name'], 0, 1)) . "
                        </div>
                        <div>
                            <p class='font-bold text-gray-800 dark:text-white text-sm'>{$clientName} {$urgencyIcon}</p>
                            <p class='text-xs text-gray-400'>" . htmlspecialchars($c['client_document'] ?? '') . "</p>
                        </div>
                    </div>
                </td>
                <td class='p-4 text-right'>
                    <p class='text-sm font-bold text-gray-700 dark:text-gray-200'>\${$total}</p>
                </td>
                <td class='p-4 text-right'>
                    <p class='text-sm font-extrabold text-red-500 dark:text-red-400'>\${$remaining}</p>
                </td>
                <td class='p-4'>
                    <div class='w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-1'>
                        <div class='bg-brand-500 h-2 rounded-full transition-all' style='width: {$pct}%'></div>
                    </div>
                    <p class='text-[10px] text-gray-400 font-bold'>{$pct}% pagado (\${$paid})</p>
                </td>
                <td class='p-4 text-center'>{$statusBadge}</td>
                <td class='p-4 text-right'>
                    <div class='flex items-center justify-end gap-1'>";

            // Botón WhatsApp de cobro
            if ($clientPhone && $c['status'] !== 'pagado') {
                $whatsappMsg = $this->buildWhatsAppMessage($c);
                $whatsappUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $clientPhone) . "?text=" . urlencode($whatsappMsg);
                echo "<a href='{$whatsappUrl}' target='_blank' class='text-green-500 hover:text-green-600 bg-green-50 dark:bg-green-900/20 p-2 rounded-lg transition-colors' title='Cobrar por WhatsApp'>
                        <i class='fab fa-whatsapp text-lg'></i>
                      </a>";
            }

            echo "  <a href='" . BASE_URL . "credits/detail/{$c['id']}' class='text-gray-400 hover:text-brand-500 bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg transition-colors' title='Ver Detalle'>
                        <i class='fas fa-eye'></i>
                      </a>
                    </div>
                </td>
            </tr>";
        }
    }

    /**
     * Vista detalle: Historial de un crédito con abonos.
     */
    public function detail($id) {
        $credit = $this->creditModel->find($id);
        if (!$credit) {
            header('Location: ' . BASE_URL . 'credits');
            exit;
        }

        // Datos del cliente
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$credit['client_id']]);
        $client = $stmt->fetch();

        // Historial de pagos
        $payments = $this->paymentModel->getByCredit($id);

        // Calcular info de vencimiento para mostrar alertas en el detalle
        $dueDateInfo = null;
        if ($credit['due_date'] && $credit['status'] !== 'pagado') {
            $today = new DateTime('today');
            $dueDate = new DateTime($credit['due_date']);
            $dueDate->setTime(0, 0, 0);
            $interval = $today->diff($dueDate);
            $diffDays = (int) $interval->format('%r%a');
            
            $dueDateInfo = [
                'diff_days' => $diffDays,
                'due_date_formatted' => $dueDate->format('d/m/Y'),
            ];
        }

        $this->view('modules/credits/views/detail', [
            'credit'       => $credit,
            'client'       => $client,
            'payments'     => $payments,
            'dueDateInfo'  => $dueDateInfo,
        ]);
    }

    /**
     * Crear un nuevo crédito (POST HTMX).
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $baseAmount = (float) ($_POST['base_amount'] ?? 0);
            $creditType = $_POST['credit_type'] ?? 'producto';
            
            $interestRate = 0;
            $downPaymentRate = 0;
            $downPaymentAmount = 0;
            $totalAmount = $baseAmount;
            
            if ($creditType === 'producto') {
                if ($baseAmount < 3) {
                    header('X-Toast-Message: El monto mínimo para créditos de productos es $3');
                    header('X-Toast-Type: error');
                    http_response_code(400);
                    exit;
                }
                $downPaymentRate = (float) ($_POST['down_payment_rate'] ?? 0);
                $downPaymentAmount = $baseAmount * ($downPaymentRate / 100);
            } else if ($creditType === 'dinero') {
                if ($baseAmount < 50) {
                    header('X-Toast-Message: El monto mínimo para préstamos de dinero es $50');
                    header('X-Toast-Type: error');
                    http_response_code(400);
                    exit;
                }
                $interestRate = (float) ($_POST['interest_rate'] ?? 0);
                $totalAmount = $baseAmount + ($baseAmount * ($interestRate / 100));
            }

            $data = [
                'client_id'        => $_POST['client_id'] ?? null,
                'sale_id'          => !empty($_POST['sale_id']) ? $_POST['sale_id'] : null,
                'credit_type'      => $creditType,
                'interest_rate'    => $interestRate,
                'down_payment'     => $downPaymentAmount,
                'base_amount'      => $baseAmount,
                'total_amount'     => $totalAmount,
                'remaining_amount' => $totalAmount,
                'due_date'         => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
                'notes'            => $_POST['notes'] ?? '',
                'created_by'       => $_SESSION['user_id'] ?? null,
                'status'           => 'activo',
            ];

            $creditId = $this->creditModel->create($data);
            
            // Si hay inicial (down_payment), registrar el abono automáticamente
            if ($creditId && $downPaymentAmount > 0) {
                $this->paymentModel->create([
                    'credit_id'      => $creditId,
                    'amount'         => $downPaymentAmount,
                    'payment_method' => 'efectivo', // Opcionalmente dinámico
                    'reference'      => 'Abono inicial automático',
                    'status'         => 'aprobado',
                    'reported_by'    => $_SESSION['user_id'] ?? null,
                    'approved_by'    => $_SESSION['user_id'] ?? null,
                    'approved_at'    => date('Y-m-d H:i:s')
                ]);
                $this->creditModel->updateBalance($creditId);
            }

            // Si tiene fecha límite, crear notificación recordatoria
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT name, email FROM clients WHERE id = ?");
            $stmt->execute([$data['client_id']]);
            $clientData = $stmt->fetch();
            $clientName = $clientData['name'] ?? 'Cliente';
            $clientEmail = $clientData['email'] ?? null;

            if ($creditId && !empty($data['due_date'])) {
                Notification::send(
                    'credito_nuevo',
                    '📋 Nuevo Crédito Registrado',
                    "Se registró un crédito de \${$totalAmount} para {$clientName}. Fecha límite: " . date('d/m/Y', strtotime($data['due_date'])),
                    'administrador',
                    'credit_alert',
                    $creditId
                );
                
                // Enviar correo al administrador
                $stmtAdmin = $db->prepare("SELECT email FROM businesses WHERE id = ?");
                $stmtAdmin->execute([$_SESSION['business_id'] ?? 0]);
                $adminData = $stmtAdmin->fetch();
                if ($adminData && !empty($adminData['email'])) {
                    require_once __DIR__ . '/../../../core/Mailer.php';
                    require_once __DIR__ . '/../../../core/EmailTemplates.php';
                    $clientPhone = $clientData['phone'] ?? 'No especificado'; // Requires fetching phone, we can just use the phone from DB if available. Wait, let's select phone above.
                    
                    // fetch phone
                    $stmtPhone = $db->prepare("SELECT phone FROM clients WHERE id = ?");
                    $stmtPhone->execute([$data['client_id']]);
                    $clientPhone = $stmtPhone->fetchColumn() ?: 'No especificado';

                    $loginUrl = BASE_URL . "dashboard/creditos/detalles/" . $creditId;
                    $emailHtml = EmailTemplates::getCreditRequestEmail($clientName, $clientPhone, $loginUrl);
                    Mailer::send($adminData['email'], "Nuevo Crédito Registrado - " . $clientName, $emailHtml);
                }
            }

            // Enviar notificación al correo del cliente
            if ($creditId && !empty($clientEmail)) {
                try {
                    require_once __DIR__ . '/../../../core/Mailer.php';
                    require_once __DIR__ . '/../../../core/Settings.php';
                    $stmtBizName = $db->prepare("SELECT business_name FROM businesses WHERE id = ?");
                    $stmtBizName->execute([$_SESSION['business_id']]);
                    $bizName = $stmtBizName->fetchColumn() ?: 'Su Tienda';
                    
                    $msgText = "Hola {$clientName},<br><br>Se ha registrado un " . ($creditType === 'producto' ? "crédito por productos" : "préstamo") . " a su nombre en <b>{$bizName}</b> por un monto de <b>\${$totalAmount}</b>.<br><br>";
                    if ($downPaymentAmount > 0) {
                        $msgText .= "Abono inicial registrado: \${$downPaymentAmount}.<br>";
                        $msgText .= "Saldo pendiente: \$" . ($totalAmount - $downPaymentAmount) . ".<br><br>";
                    }
                    if (!empty($data['due_date'])) {
                        $msgText .= "Por favor, recuerde que la fecha límite sugerida de pago es el " . date('d/m/Y', strtotime($data['due_date'])) . ".<br><br>";
                    }
                    $msgText .= "Gracias por su confianza.";
                    
                    Mailer::send($clientEmail, 'Notificación de Crédito Nuevo - ' . $bizName, $msgText);
                } catch (\Exception $e) {}
            }

            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                header('HX-Trigger: creditsUpdated');
                header('X-Toast-Message: Crédito registrado exitosamente');
                header('X-Toast-Type: success');
                http_response_code(200);
                exit;
            }
            header('Location: ' . BASE_URL . 'credits');
            exit;
        }
    }

    /**
     * Registrar un abono / pago (POST HTMX).
     */
    public function payment() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $creditId = $_POST['credit_id'] ?? null;
            $amount = (float) ($_POST['amount'] ?? 0);

            if (!$creditId || $amount <= 0) {
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    header('X-Toast-Message: Monto inválido. Debe ser mayor a 0.');
                    header('X-Toast-Type: error');
                    http_response_code(400);
                    exit;
                }
                $this->jsonResponse(['error' => 'Datos inválidos'], 400);
                exit;
            }

            $credit = $this->creditModel->find($creditId);
            if (!$credit) {
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    header('X-Toast-Message: Crédito no encontrado.');
                    header('X-Toast-Type: error');
                    http_response_code(404);
                    exit;
                }
                $this->jsonResponse(['error' => 'Crédito no encontrado'], 404);
                exit;
            }

            if ($amount > $credit['remaining_amount']) {
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    header('X-Toast-Message: El abono supera el monto de la deuda ($' . $credit['remaining_amount'] . ').');
                    header('X-Toast-Type: error');
                    http_response_code(400);
                    exit;
                }
                $this->jsonResponse(['error' => 'Monto excedente'], 400);
                exit;
            }

            $paymentId = $this->paymentModel->create([
                'credit_id'      => $creditId,
                'amount'         => $amount,
                'payment_method' => $_POST['payment_method'] ?? 'efectivo',
                'reference'      => $_POST['reference'] ?? '',
                'notes'          => $_POST['notes'] ?? '',
                'reported_by'    => $_SESSION['user_id'] ?? null,
                'status'         => 'pendiente',
            ]);

            // Crear notificación para admin (Credit already fetched)
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT name FROM clients WHERE id = ?");
            $stmt->execute([$credit['client_id']]);
            $clientName = $stmt->fetchColumn() ?: 'Cliente';

            $reporterName = $_SESSION['username'] ?? 'Un vendedor';

            Notification::send(
                'pago_reportado',
                'Pago Reportado',
                "{$reporterName} ha reportado un abono de \${$amount} para {$clientName}",
                'administrador',
                'credit_payment',
                $paymentId
            );

            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                header('HX-Trigger: paymentsUpdated, creditsUpdated');
                header('X-Toast-Message: Abono reportado — pendiente de aprobación');
                header('X-Toast-Type: success');
                http_response_code(200);
                exit;
            }
            header('Location: ' . BASE_URL . 'credits/detail/' . $creditId);
            exit;
        }
    }

    /**
     * Aprobar un pago (POST HTMX - Solo Admin).
     */
    public function approve() {
        Middleware::requireRole(['administrador']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentId = $_POST['payment_id'] ?? null;
            if (!$paymentId) {
                $this->jsonResponse(['error' => 'ID inválido'], 400);
            }

            $this->paymentModel->approve($paymentId, $_SESSION['user_id']);

            // Marcar notificación como leída
            if (!empty($_POST['notification_id'])) {
                $notifModel = new Notification();
                $notifModel->markAsRead($_POST['notification_id']);
            }

            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                header('HX-Trigger: creditsUpdated, paymentsUpdated, notificationsUpdated');
                header('X-Toast-Message: ✅ Pago aprobado y saldo actualizado');
                header('X-Toast-Type: success');
                http_response_code(200);
                exit;
            }
            header('Location: ' . BASE_URL . 'credits');
            exit;
        }
    }

    /**
     * Rechazar un pago (POST HTMX - Solo Admin).
     */
    public function reject() {
        Middleware::requireRole(['administrador']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentId = $_POST['payment_id'] ?? null;
            if (!$paymentId) {
                $this->jsonResponse(['error' => 'ID inválido'], 400);
            }

            $this->paymentModel->reject($paymentId, $_SESSION['user_id']);

            if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                header('HX-Trigger: creditsUpdated, paymentsUpdated, notificationsUpdated');
                header('X-Toast-Message: Pago rechazado');
                header('X-Toast-Type: warning');
                http_response_code(200);
                exit;
            }
            header('Location: ' . BASE_URL . 'credits');
            exit;
        }
    }

    /**
     * API: Lista de notificaciones no leídas (para el dropdown de la campana).
     */
    public function notifications() {
        $notifModel = new Notification();
        $role = $_SESSION['role'] ?? 'vendedor';
        $userId = $_SESSION['user_id'] ?? null;
        $notifs = $notifModel->getUnread($role, $userId);

        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            if (empty($notifs)) {
                echo "<div class='p-6 text-center text-gray-400 dark:text-gray-500'>
                        <i class='fas fa-bell-slash text-2xl mb-2 block opacity-50'></i>
                        <p class='text-sm font-medium'>Sin notificaciones nuevas</p>
                      </div>";
                return;
            }

            foreach ($notifs as $n) {
                // Iconos según tipo de notificación
                $iconMap = [
                    'pago_reportado' => 'fa-money-bill-wave text-green-500',
                    'vence_manana'   => 'fa-clock text-yellow-500',
                    'vence_hoy'      => 'fa-exclamation-circle text-red-500',
                    'atrasado'       => 'fa-exclamation-triangle text-red-600',
                    'credito_nuevo'  => 'fa-file-invoice-dollar text-blue-500',
                ];
                $icon = $iconMap[$n['type']] ?? 'fa-bell text-amber-500';
                
                // Fondo según urgencia
                $bgClass = '';
                if (in_array($n['type'], ['vence_hoy', 'atrasado'])) {
                    $bgClass = 'bg-red-50/50 dark:bg-red-900/10';
                } elseif ($n['type'] === 'vence_manana') {
                    $bgClass = 'bg-yellow-50/50 dark:bg-yellow-900/10';
                }

                $timeAgo = $this->timeAgo($n['created_at']);
                $message = htmlspecialchars($n['message']);
                $title = htmlspecialchars($n['title'] ?? '');

                // Determinar acción al hacer clic
                $clickAction = '';
                if ($n['reference_type'] === 'credit_payment') {
                    $clickAction = "onclick=\"window.dispatchEvent(new CustomEvent('open-approval', {detail: {paymentId: {$n['reference_id']}, notifId: {$n['id']}}}))\"";
                } elseif ($n['reference_type'] === 'credit_alert' && $n['reference_id']) {
                    $clickAction = "onclick=\"window.location.href='" . BASE_URL . "credits/detail/{$n['reference_id']}'\"";
                }

                echo "
                <div class='px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 last:border-0 cursor-pointer transition-colors {$bgClass}'
                     hx-post='" . BASE_URL . "credits/notifications_read'
                     hx-vals='{\"id\": {$n['id']}}'
                     hx-swap='none'
                     {$clickAction}>
                    <div class='flex items-start gap-3'>
                        <div class='w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0 mt-0.5'>
                            <i class='fas {$icon} text-sm'></i>
                        </div>
                        <div class='flex-1 min-w-0'>
                            <p class='text-xs font-black text-gray-600 dark:text-gray-300 uppercase tracking-wider mb-0.5'>{$title}</p>
                            <p class='text-sm font-semibold text-gray-800 dark:text-gray-100 leading-snug'>{$message}</p>
                            <p class='text-xs text-gray-400 mt-1'><i class='fas fa-clock mr-1'></i>{$timeAgo}</p>
                        </div>
                    </div>
                </div>";
            }
            return;
        }

        $this->jsonResponse($notifs);
    }

    /**
     * API: Contador de notificaciones no leídas (badge).
     */
    public function notifications_count() {
        $notifModel = new Notification();
        $count = $notifModel->countUnread($_SESSION['role'] ?? 'vendedor', $_SESSION['user_id'] ?? null);

        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            if ($count > 0) {
                echo "<span class='absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full shadow-sm animate-pulse'>{$count}</span>";
            }
            return;
        }

        $this->jsonResponse(['count' => $count]);
    }

    /**
     * Marcar notificación como leída (HTMX).
     */
    public function notifications_read() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            $notifModel = new Notification();
            $notifModel->markAsRead($_POST['id']);
        }
        header('HX-Trigger: notificationsUpdated');
        http_response_code(200);
        exit;
    }

    /**
     * Marcar todas como leídas (HTMX).
     */
    public function notifications_read_all() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $notifModel = new Notification();
            $notifModel->markAllAsRead($_SESSION['role'] ?? 'vendedor', $_SESSION['user_id'] ?? null);
        }
        header('HX-Trigger: notificationsUpdated');
        http_response_code(200);
        exit;
    }

    /**
     * API: Obtener datos de un pago pendiente (para el modal de aprobación).
     */
    public function payment_detail($id) {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            echo "<p class='text-center text-gray-400 p-6'>Pago no encontrado</p>";
            return;
        }

        $credit = $this->creditModel->find($payment['credit_id']);
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT name, phone FROM clients WHERE id = ?");
        $stmt->execute([$credit['client_id']]);
        $client = $stmt->fetch();

        $stmtUser = $db->prepare("SELECT username FROM users WHERE id = ?");
        $stmtUser->execute([$payment['reported_by']]);
        $reporterName = $stmtUser->fetchColumn() ?: 'Desconocido';

        $amount = number_format($payment['amount'], 2);
        $method = htmlspecialchars(ucfirst($payment['payment_method'] ?? 'N/A'));
        $reference = htmlspecialchars($payment['reference'] ?? '—');
        $clientName = htmlspecialchars($client['name'] ?? 'Cliente');
        $date = date('d/m/Y H:i', strtotime($payment['created_at']));

        echo "
        <div class='space-y-4'>
            <div class='flex items-center gap-3 p-4 bg-brand-50 dark:bg-brand-900/20 rounded-xl border border-brand-200 dark:border-brand-800'>
                <div class='w-12 h-12 bg-brand-100 dark:bg-brand-900/40 rounded-xl flex items-center justify-center'>
                    <i class='fas fa-money-bill-wave text-brand-600 text-xl'></i>
                </div>
                <div>
                    <p class='text-2xl font-black text-brand-700 dark:text-brand-300'>\${$amount}</p>
                    <p class='text-sm text-brand-500'>Abono reportado</p>
                </div>
            </div>
            <div class='grid grid-cols-2 gap-3'>
                <div class='bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3'>
                    <p class='text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1'>Cliente</p>
                    <p class='text-sm font-bold text-gray-800 dark:text-white'>{$clientName}</p>
                </div>
                <div class='bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3'>
                    <p class='text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1'>Reportado por</p>
                    <p class='text-sm font-bold text-gray-800 dark:text-white'>{$reporterName}</p>
                </div>
                <div class='bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3'>
                    <p class='text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1'>Método</p>
                    <p class='text-sm font-bold text-gray-800 dark:text-white'>{$method}</p>
                </div>
                <div class='bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3'>
                    <p class='text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-1'>Referencia</p>
                    <p class='text-sm font-bold text-gray-800 dark:text-white'>{$reference}</p>
                </div>
            </div>
            <p class='text-xs text-gray-400 text-center'><i class='fas fa-clock mr-1'></i>Reportado el {$date}</p>
        </div>";
    }

    /**
     * Endpoint para verificar alertas vía AJAX (polling desde el frontend).
     * Retorna las alertas activas como JSON para notificaciones push del navegador.
     */
    public function check_alerts() {
        $result = $this->creditModel->checkOverdueCredits();
        $this->jsonResponse($result);
    }

    // ============================================================
    // Helpers Privados
    // ============================================================

    private function getStatusBadge($status) {
        $badges = [
            'activo'    => "<span class='inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'><i class='fas fa-circle text-[6px]'></i>Activo</span>",
            'pagado'    => "<span class='inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'><i class='fas fa-check-circle text-[10px]'></i>Pagado</span>",
            'atrasado'  => "<span class='inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 animate-pulse'><i class='fas fa-exclamation-triangle text-[10px]'></i>Atrasado</span>",
            'cancelado' => "<span class='inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'><i class='fas fa-ban text-[10px]'></i>Cancelado</span>",
        ];
        return $badges[$status] ?? $badges['activo'];
    }

    private function buildWhatsAppMessage($credit) {
        $clientName = $credit['client_name'];
        $remaining = number_format($credit['remaining_amount'], 2);
        $businessName = Settings::get('business_name', 'Tu Inventario');

        return "Hola {$clientName} 👋\n\n"
             . "Le escribimos de *{$businessName}* para recordarle que tiene un saldo pendiente de *\${$remaining}*.\n\n"
             . "¿Desea realizar un abono o consultar su estado de cuenta? Estamos a su orden. 🙏\n\n"
             . "— {$businessName}";
    }

    private function timeAgo($datetime) {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) return 'hace un momento';
        if ($diff < 3600) return 'hace ' . floor($diff / 60) . ' min';
        if ($diff < 86400) return 'hace ' . floor($diff / 3600) . 'h';
        return 'hace ' . floor($diff / 86400) . 'd';
    }

    /**
     * Obtiene resumen de créditos (para el dashboard u otros módulos).
     */
    public function summary() {
        $summary = $this->creditModel->getSummary();
        $this->jsonResponse($summary);
    }

    /**
     * API: Lista de clientes para selector en formulario.
     */
    public function clients_list() {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT id, name, document, phone FROM clients";
        $params = [];
        if (isset($_SESSION['business_id'])) {
            $sql .= " WHERE tenant_id = ?";
            $params[] = $_SESSION['business_id'];
        }
        $sql .= " ORDER BY name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $clients = $stmt->fetchAll();

        foreach ($clients as $c) {
            $name = htmlspecialchars($c['name']);
            $doc = htmlspecialchars($c['document'] ?? '');
            echo "<option value='{$c['id']}'>{$name}" . ($doc ? " ({$doc})" : "") . "</option>";
        }
    }

    /**
     * API: Registra/Actualiza una suscripción Push (VAPID) desde el navegador
     */
    public function push_subscribe() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!empty($data['endpoint']) && !empty($data['keys']['p256dh']) && !empty($data['keys']['auth'])) {
                $db = Database::getInstance()->getConnection();
                
                $userId = $_SESSION['user_id'] ?? null;
                $role = $_SESSION['role'] ?? 'invitado';
                
                // Usamos UPSERT (ON CONFLICT) de SQLite o INSERT IGNORE manual
                $sql = "INSERT INTO push_subscriptions (user_id, role, endpoint, p256dh, auth) VALUES (?, ?, ?, ?, ?) 
                        ON CONFLICT(endpoint) DO UPDATE SET user_id = excluded.user_id, role = excluded.role";
                $stmt = $db->prepare($sql);
                try {
                    $stmt->execute([
                        $userId,
                        $role,
                        trim($data['endpoint']),
                        trim($data['keys']['p256dh']),
                        trim($data['keys']['auth'])
                    ]);
                    $this->jsonResponse(['success' => true]);
                } catch (\Exception $e) {
                    $this->jsonResponse(['error' => $e->getMessage()], 500);
                }
            } else {
                $this->jsonResponse(['error' => 'Invalid push data'], 400);
            }
        }
    }

    /**
     * Imprimir ticket de compromiso legal
     */
    public function ticket($id) {
        $credit = $this->creditModel->find($id);
        if (!$credit) {
            die("Crédito no encontrado.");
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$credit['client_id']]);
        $client = $stmt->fetch();

        $stmtBusiness = $db->prepare("SELECT * FROM businesses WHERE id = ?");
        $stmtBusiness->execute([$credit['tenant_id'] ?? $_SESSION['business_id']]);
        $business = $stmtBusiness->fetch();

        $this->view('modules/credits/views/ticket', [
            'credit'   => $credit,
            'client'   => $client,
            'business' => $business
        ]);
    }
}
