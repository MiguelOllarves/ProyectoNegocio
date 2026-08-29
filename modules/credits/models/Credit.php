<?php
require_once __DIR__ . '/../../../core/Model.php';

class Credit extends Model {
    protected $table = 'credits';
    protected $tenantColumn = 'tenant_id';

    /**
     * Obtiene todos los créditos activos de un cliente específico.
     */
    public function getByClient($clientId) {
        $sql = "SELECT c.*, cl.name as client_name, cl.phone as client_phone
                FROM credits c
                JOIN clients cl ON cl.id = c.client_id
                WHERE c.client_id = :client_id";
        $params = ['client_id' => $clientId];

        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $sql .= " AND c.tenant_id = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }
        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene todos los créditos con datos del cliente (para la vista de directorio).
     */
    public function allWithClients($statusFilter = null) {
        $sql = "SELECT c.*, cl.name as client_name, cl.phone as client_phone, cl.document as client_document, cl.email as client_email
                FROM credits c
                JOIN clients cl ON cl.id = c.client_id
                WHERE 1=1";
        $params = [];

        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $sql .= " AND c.tenant_id = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }
        if ($statusFilter) {
            $sql .= " AND c.status = :status";
            $params['status'] = $statusFilter;
        }
        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allWithClientsPaginated($statusFilter = null, $limit = 5, $offset = 0) {
        $where = "WHERE 1=1";
        $params = [];

        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $where .= " AND c.tenant_id = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }

        if ($statusFilter) {
            $where .= " AND c.status = :status";
            $params['status'] = $statusFilter;
        }

        $countSql = "SELECT COUNT(*) FROM credits c JOIN clients cl ON cl.id = c.client_id $where";
        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();

        $sql = "SELECT c.*, cl.name as client_name, cl.phone as client_phone, cl.document as client_document, cl.email as client_email
                FROM credits c
                JOIN clients cl ON cl.id = c.client_id
                $where
                ORDER BY CASE 
                    WHEN c.status = 'Vencido' THEN 1 
                    WHEN c.status = 'Pendiente' THEN 2 
                    ELSE 3 
                END, c.due_date ASC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        return [
            'data' => $data,
            'total' => $total
        ];
    }

    /**
     * Recalcula el saldo de un crédito basándose en los pagos aprobados.
     */
    public function updateBalance($creditId) {
        // Sumar todos los pagos aprobados
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM credit_payments WHERE credit_id = :id AND status = 'aprobado'");
        $stmt->execute(['id' => $creditId]);
        $totalPaid = (float) $stmt->fetchColumn();

        // Obtener el monto total del crédito
        $credit = $this->find($creditId);
        if (!$credit) return false;

        $remaining = $credit['total_amount'] - $totalPaid;
        $status = $remaining <= 0 ? 'pagado' : $credit['status'];
        if ($remaining <= 0) $remaining = 0;

        return $this->update($creditId, [
            'paid_amount'      => $totalPaid,
            'remaining_amount' => $remaining,
            'status'           => $status,
        ]);
    }

    /**
     * Resumen general de créditos (para el dashboard).
     */
    public function getSummary() {
        $sql = "SELECT 
                    COUNT(*) as total_credits,
                    COALESCE(SUM(CASE WHEN status = 'activo' THEN remaining_amount ELSE 0 END), 0) as total_pending,
                    COALESCE(SUM(CASE WHEN status = 'atrasado' THEN remaining_amount ELSE 0 END), 0) as total_overdue,
                    COUNT(CASE WHEN status = 'activo' THEN 1 END) as active_count,
                    COUNT(CASE WHEN status = 'atrasado' THEN 1 END) as overdue_count
                FROM credits WHERE 1=1";
        $params = [];

        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $sql .= " AND tenant_id = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * =========================================================
     * SISTEMA DE ALERTAS AUTOMÁTICAS DE CRÉDITOS
     * Se ejecuta en cada carga de página para verificar 
     * vencimientos y generar notificaciones en tiempo real.
     * =========================================================
     */
    public function checkOverdueCredits() {
        $tenantId = $_SESSION['business_id'] ?? null;
        if (!$tenantId) return ['alerts' => [], 'updated' => 0];

        // Obtener TODOS los créditos activos o atrasados con fecha de vencimiento
        $sql = "SELECT c.*, cl.name as client_name, cl.phone as client_phone, cl.email as client_email
                FROM credits c
                JOIN clients cl ON cl.id = c.client_id
                WHERE c.status IN ('activo', 'atrasado')
                AND c.due_date IS NOT NULL
                AND c.tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId]);
        $credits = $stmt->fetchAll();

        $today = new DateTime('today');
        $alerts = [];
        $updatedCount = 0;

        foreach ($credits as $credit) {
            $dueDate = new DateTime($credit['due_date']);
            $dueDate->setTime(0, 0, 0);

            // diffDays: positivo = faltan días, 0 = hoy, negativo = ya pasó
            $interval = $today->diff($dueDate);
            $diffDays = (int) $interval->format('%r%a');

            $alertType = null;
            $alertLevel = 'info'; // info, warning, danger
            $title = '';
            $message = '';
            $icon = '';

            if ($diffDays === 1 && $credit['status'] === 'activo') {
                // VENCE MAÑANA
                $alertType = 'vence_manana';
                $alertLevel = 'warning';
                $title = '⏰ Crédito vence MAÑANA';
                $message = "El crédito de \${$credit['remaining_amount']} de {$credit['client_name']} vence mañana (" . $dueDate->format('d/m/Y') . ").";
                $icon = 'fa-clock';

            } elseif ($diffDays === 0 && $credit['status'] === 'activo') {
                // VENCE HOY
                $alertType = 'vence_hoy';
                $alertLevel = 'danger';
                $title = '🔴 Crédito vence HOY';
                $message = "El crédito de \${$credit['remaining_amount']} de {$credit['client_name']} VENCE HOY.";
                $icon = 'fa-exclamation-circle';

            } elseif ($diffDays < 0) {
                // YA VENCIÓ - ATRASADO
                $daysOverdue = abs($diffDays);
                $alertType = 'atrasado';
                $alertLevel = 'danger';
                $icon = 'fa-exclamation-triangle';

                if ($daysOverdue === 1) {
                    $title = '🚨 Crédito se venció AYER';
                    $message = "El crédito de \${$credit['remaining_amount']} de {$credit['client_name']} se venció ayer.";
                } else {
                    $title = "🚨 Crédito atrasado ($daysOverdue días)";
                    $message = "El crédito de \${$credit['remaining_amount']} de {$credit['client_name']} tiene $daysOverdue días de atraso.";
                }

                // Cambiar estado a 'atrasado' si aún está en 'activo'
                if ($credit['status'] === 'activo') {
                    $updateStmt = $this->db->prepare("UPDATE credits SET status = 'atrasado', updated_at = CURRENT_TIMESTAMP WHERE id = :id");
                    $updateStmt->execute(['id' => $credit['id']]);
                    $updatedCount++;
                }
            }

            if ($alertType) {
                // Verificar si ya se envió una notificación de este tipo HOY para este crédito
                $todayStr = $today->format('Y-m-d');
                $checkSql = "SELECT COUNT(*) FROM notifications 
                             WHERE tenant_id = :tenant_id 
                             AND reference_type = 'credit_alert' 
                             AND reference_id = :credit_id
                             AND type = :type
                             AND DATE(created_at) = :today";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([
                    'tenant_id' => $tenantId,
                    'credit_id' => $credit['id'],
                    'type'      => $alertType,
                    'today'     => $todayStr,
                ]);
                $alreadySent = (int) $checkStmt->fetchColumn();

                if ($alreadySent === 0) {
                    // Crear notificación in-app para el administrador
                    $notifSql = "INSERT INTO notifications (tenant_id, target_role, type, title, message, reference_type, reference_id) 
                                 VALUES (:tenant_id, 'administrador', :type, :title, :message, 'credit_alert', :credit_id)";
                    $notifStmt = $this->db->prepare($notifSql);
                    $notifStmt->execute([
                        'tenant_id' => $tenantId,
                        'type'      => $alertType,
                        'title'     => $title,
                        'message'   => $message,
                        'credit_id' => $credit['id'],
                    ]);

                    // Enviar alerta por correo al dueño del tenant
                    require_once __DIR__ . '/../../../core/Mailer.php';
                    $stmtTenant = $this->db->prepare("SELECT email FROM businesses WHERE id = ?");
                    $stmtTenant->execute([$tenantId]);
                    $tenantEmail = $stmtTenant->fetchColumn();
                    if ($tenantEmail) {
                        $emailHtml = "<h2 style='color:#ef4444;'>Alerta de Crédito: {$title}</h2>
                                      <p>{$message}</p>
                                      <p>El cliente <strong>{$credit['client_name']}</strong> tiene un saldo pendiente de <strong>\${$credit['remaining_amount']}</strong>.</p>";
                        Mailer::send($tenantEmail, "Alerta de Sistema Automática: {$title}", $emailHtml);
                    }
                }

                $alerts[] = [
                    'credit_id'    => $credit['id'],
                    'client_name'  => $credit['client_name'],
                    'client_phone' => $credit['client_phone'] ?? '',
                    'client_email' => $credit['client_email'] ?? '',
                    'remaining'    => $credit['remaining_amount'],
                    'due_date'     => $credit['due_date'],
                    'days_diff'    => $diffDays,
                    'alert_type'   => $alertType,
                    'alert_level'  => $alertLevel,
                    'title'        => $title,
                    'message'      => $message,
                    'icon'         => $icon,
                ];
            }
        }

        return ['alerts' => $alerts, 'updated' => $updatedCount];
    }
}
