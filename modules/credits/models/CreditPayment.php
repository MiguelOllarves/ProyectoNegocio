<?php
require_once __DIR__ . '/../../../core/Model.php';

class CreditPayment extends Model {
    protected $table = 'credit_payments';

    /**
     * Obtiene todos los abonos de un crédito específico con nombre de quien reportó/aprobó.
     */
    public function getByCredit($creditId) {
        $sql = "SELECT cp.*, 
                    u_rep.username as reported_by_name,
                    u_apr.username as approved_by_name
                FROM credit_payments cp
                LEFT JOIN users u_rep ON u_rep.id = cp.reported_by
                LEFT JOIN users u_apr ON u_apr.id = cp.approved_by
                WHERE cp.credit_id = :credit_id
                ORDER BY cp.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['credit_id' => $creditId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene todos los pagos pendientes de aprobación (para la central de notificaciones del admin).
     */
    public function getPending() {
        $sql = "SELECT cp.*, c.total_amount, c.remaining_amount,
                    cl.name as client_name,
                    u.username as reported_by_name
                FROM credit_payments cp
                JOIN credits c ON c.id = cp.credit_id
                JOIN clients cl ON cl.id = c.client_id
                LEFT JOIN users u ON u.id = cp.reported_by
                WHERE cp.status = 'pendiente'
                ORDER BY cp.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Aprueba un pago y actualiza el saldo del crédito asociado.
     */
    public function approve($paymentId, $adminUserId) {
        $this->update($paymentId, [
            'status'      => 'aprobado',
            'approved_by' => $adminUserId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        // Recalcular saldo del crédito
        $payment = $this->find($paymentId);
        if ($payment) {
            require_once __DIR__ . '/Credit.php';
            $creditModel = new Credit();
            $creditModel->updateBalance($payment['credit_id']);
        }

        return true;
    }

    /**
     * Rechaza un pago.
     */
    public function reject($paymentId, $adminUserId) {
        return $this->update($paymentId, [
            'status'      => 'rechazado',
            'approved_by' => $adminUserId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
