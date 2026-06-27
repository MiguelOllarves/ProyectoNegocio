<?php
require_once __DIR__ . '/../../../core/Model.php';

class Cashbox extends Model {
    protected $table = 'arqueo_caja';

    /**
     * Obtener sesión abierta del usuario actual
     */
    public function getOpenSession($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = :user_id AND estado = 'abierta' ORDER BY id DESC LIMIT 1");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Abrir una nueva sesión de caja
     */
    public function openSession($user_id, $monto_usd, $monto_bs = 0) {
        if ($this->getOpenSession($user_id)) {
            return false; // Ya hay una caja abierta
        }
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, monto_inicial_usd, monto_inicial_bs, estado) VALUES (:user_id, :monto_usd, :monto_bs, 'abierta')");
        $res = $stmt->execute([
            'user_id'   => $user_id,
            'monto_usd' => $monto_usd,
            'monto_bs'  => $monto_bs,
        ]);
        if ($res) {
            $this->logAudit('CREATE', $this->db->lastInsertId(), ['action' => 'APERTURA_CAJA', 'monto_usd' => $monto_usd, 'monto_bs' => $monto_bs]);
        }
        return $res;
    }

    /**
     * Cerrar sesión de caja con los montos declarados y cálculo de diferencia
     */
    public function closeSession($id, $ventas_usd, $ventas_bs, $declarado_usd, $declarado_bs, $notes = '') {
        $session = $this->find($id);
        if (!$session) return false;

        $esperado_usd = $session['monto_inicial_usd'] + $ventas_usd;
        $esperado_bs  = $session['monto_inicial_bs'] + $ventas_bs;
        $diff_usd     = $declarado_usd - $esperado_usd;
        $diff_bs      = $declarado_bs - $esperado_bs;

        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET 
                fecha_cierre = CURRENT_TIMESTAMP,
                ventas_usd = :ventas_usd,
                ventas_bs = :ventas_bs,
                declarado_usd = :declarado_usd,
                declarado_bs = :declarado_bs,
                diferencia_usd = :diff_usd,
                diferencia_bs = :diff_bs,
                estado = 'cerrada'
            WHERE id = :id
        ");
        $res = $stmt->execute([
            'ventas_usd'   => $ventas_usd,
            'ventas_bs'    => $ventas_bs,
            'declarado_usd'=> $declarado_usd,
            'declarado_bs' => $declarado_bs,
            'diff_usd'     => $diff_usd,
            'diff_bs'      => $diff_bs,
            'id'           => $id
        ]);
        if ($res) {
            $this->logAudit('UPDATE', $id, [
                'action' => 'CIERRE_CAJA',
                'esperado_usd' => $esperado_usd,
                'declarado_usd' => $declarado_usd,
                'diferencia_usd' => $diff_usd
            ]);
        }
        return $res;
    }

    /**
     * Historial de todas las sesiones con nombre de usuario
     */
    public function allSessionsWithUsers() {
        $stmt = $this->db->prepare("
            SELECT a.*, u.username 
            FROM {$this->table} a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.fecha_apertura DESC
            LIMIT 50
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
