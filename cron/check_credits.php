<?php
/**
 * Script para verificar diariamente los créditos vencidos o por vencer.
 * Ejecutar vía Cron (ej. 0 8 * * * php /ruta/al/proyecto/cron/check_credits.php)
 */

// Ignorar el acceso web (Opcional, pero recomendado)
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ser ejecutado por consola (CLI).");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../modules/credits/models/Notification.php';
require_once __DIR__ . '/../core/Mailer.php'; // Lo crearemos a continuación

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Obtener todos los créditos activos o atrasados que tienen una fecha de vencimiento
    $sql = "SELECT c.*, cl.name as client_name, cl.email as client_email, cl.phone as client_phone, b.business_name 
            FROM credits c
            JOIN clients cl ON cl.id = c.client_id
            JOIN businesses b ON b.id = c.tenant_id
            WHERE c.status IN ('activo', 'atrasado') 
            AND c.due_date IS NOT NULL";
            
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $credits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $today = new DateTime('today');
    
    $processedCount = 0;
    $updatedCount = 0;
    
    foreach ($credits as $credit) {
        $dueDate = new DateTime($credit['due_date']);
        
        // Ignorar horas, comparar solo días
        $dueDate->setTime(0, 0, 0);
        
        $diffDays = (int)$today->diff($dueDate)->format('%R%a'); 
        // %R%a: +1 (mañana), 0 (hoy), -1 (ayer)
        
        $alertType = null;
        $title = '';
        $message = '';
        
        if ($diffDays === 1 && $credit['status'] === 'activo') {
            // Vence mañana
            $alertType = 'vence_manana';
            $title = 'Crédito vence mañana';
            $message = "El crédito de \${$credit['remaining_amount']} del cliente {$credit['client_name']} vence mañana.";
        } elseif ($diffDays === 0 && $credit['status'] === 'activo') {
            // Vence hoy
            $alertType = 'vence_hoy';
            $title = 'Crédito vence HOY';
            $message = "El crédito de \${$credit['remaining_amount']} del cliente {$credit['client_name']} vence hoy.";
        } elseif ($diffDays < 0) {
            // Ya venció
            $alertType = 'atrasado';
            $title = 'Crédito atrasado';
            
            if ($diffDays === -1) {
                $message = "El crédito de \${$credit['remaining_amount']} del cliente {$credit['client_name']} se venció ayer.";
            } else {
                $daysOverdue = abs($diffDays);
                $message = "El crédito de \${$credit['remaining_amount']} del cliente {$credit['client_name']} tiene $daysOverdue días de atraso.";
            }
            
            // Actualizar estado a 'atrasado' si aún es 'activo'
            if ($credit['status'] === 'activo') {
                $updateStmt = $db->prepare("UPDATE credits SET status = 'atrasado', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $updateStmt->execute([$credit['id']]);
                $updatedCount++;
            }
        }
        
        if ($alertType) {
            // 1. Notificación In-App (Campanita) para el administrador de ese negocio (tenant_id)
            $notifSql = "INSERT INTO notifications (tenant_id, target_role, type, title, message, reference_type, reference_id) 
                         VALUES (?, 'administrador', ?, ?, ?, 'credit_alert', ?)";
            $notifStmt = $db->prepare($notifSql);
            $notifStmt->execute([
                $credit['tenant_id'],
                $alertType,
                $title,
                $message,
                $credit['id']
            ]);
            
            // 2. Alerta por Email al Cliente (Si tiene email)
            if (!empty($credit['client_email'])) {
                $subject = "Recordatorio de Pago - {$credit['business_name']}";
                $body = "Hola {$credit['client_name']},\n\n";
                $body .= "Este es un recordatorio de su cuenta en {$credit['business_name']}.\n\n";
                $body .= "Monto Pendiente: \${$credit['remaining_amount']}\n";
                $body .= "Fecha de Vencimiento: " . date('d/m/Y', strtotime($credit['due_date'])) . "\n\n";
                
                if ($alertType === 'vence_manana') {
                    $body .= "Le recordamos que su fecha límite de pago es mañana. Por favor, realice su abono a tiempo para evitar recargos.\n";
                } elseif ($alertType === 'vence_hoy') {
                    $body .= "Le recordamos que su pago vence HOY.\n";
                } elseif ($alertType === 'atrasado') {
                    $body .= "Le informamos que su fecha límite de pago ha expirado. Por favor, comuníquese con nosotros a la brevedad posible.\n";
                }
                
                Mailer::send($credit['client_email'], $subject, $body);
            }
            
            // 3. Simulación de WhatsApp / Push
            if (!empty($credit['client_phone'])) {
                $logMsg = "[WhatsApp Simulation] A {$credit['client_phone']} ({$credit['client_name']}): $message\n";
                error_log($logMsg);
            }
            
            $processedCount++;
        }
    }
    
    echo "Proceso completado. $processedCount alertas generadas. $updatedCount créditos actualizados a atrasados.\n";
    
} catch (Exception $e) {
    echo "Error en cron de créditos: " . $e->getMessage() . "\n";
}
