<?php
require_once __DIR__ . '/../../../core/Model.php';

class Cashbox extends Model {
    protected $table = 'arqueo_caja';

    public function __construct() {
        parent::__construct();
        // Asegurar que la tabla exista para evitar errores
        $autoInc = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 'SERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        
        $this->db->exec("CREATE TABLE IF NOT EXISTS arqueo_caja (
            id {$autoInc},
            user_id INTEGER NOT NULL,
            monto_inicial_usd REAL DEFAULT 0,
            monto_inicial_bs REAL DEFAULT 0,
            ventas_usd REAL DEFAULT 0,
            ventas_bs REAL DEFAULT 0,
            declarado_usd REAL DEFAULT 0,
            declarado_bs REAL DEFAULT 0,
            diferencia_usd REAL DEFAULT 0,
            diferencia_bs REAL DEFAULT 0,
            estado TEXT DEFAULT 'abierta' CHECK(estado IN ('abierta', 'cerrada')),
            fecha_apertura DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_cierre DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
        )");
    }
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
    public function closeSession($id, $declarado_usd, $declarado_bs, $notes = '') {
        $businessId = (int)($_SESSION['business_id'] ?? 0);
        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($businessId <= 0 || $userId <= 0) return false;

        $this->db->beginTransaction();
        try {
            $sqlSession = "
                SELECT a.*
                FROM {$this->table} a
                JOIN users u ON u.id = a.user_id
                WHERE a.id = :id
                  AND u.business_id = :business_id
                  AND a.estado = 'abierta'
            ";
            $params = ['id' => $id, 'business_id' => $businessId];
            if (($_SESSION['role'] ?? '') !== 'administrador') {
                $sqlSession .= ' AND a.user_id = :user_id';
                $params['user_id'] = $userId;
            }
            $stmtSession = $this->db->prepare($sqlSession . ' FOR UPDATE');
            $stmtSession->execute($params);
            $session = $stmtSession->fetch(PDO::FETCH_ASSOC);
            if (!$session) {
                $this->db->rollBack();
                return false;
            }

            $stmtSales = $this->db->prepare("
                SELECT COALESCE(SUM(total), 0)
                FROM sales
                WHERE user_id = :user_id
                  AND created_at >= :opened_at
                  AND COALESCE(status, 'completada') <> 'anulada'
            ");
            $stmtSales->execute([
                'user_id' => $session['user_id'],
                'opened_at' => $session['fecha_apertura']
            ]);
            $ventas_usd = (float)$stmtSales->fetchColumn();

            $stmtPayments = $this->db->prepare("
                SELECT COALESCE(SUM(vp.monto_bs), 0)
                FROM ventas_pagos vp
                JOIN sales s ON s.id = vp.venta_id
                WHERE s.user_id = :user_id
                  AND s.created_at >= :opened_at
                  AND COALESCE(s.status, 'completada') <> 'anulada'
            ");
            $stmtPayments->execute([
                'user_id' => $session['user_id'],
                'opened_at' => $session['fecha_apertura']
            ]);
            $ventas_bs = (float)$stmtPayments->fetchColumn();

            $esperado_usd = (float)$session['monto_inicial_usd'] + $ventas_usd;
            $esperado_bs = (float)$session['monto_inicial_bs'] + $ventas_bs;
            $diff_usd = $declarado_usd - $esperado_usd;
            $diff_bs = $declarado_bs - $esperado_bs;

            $stmt = $this->db->prepare("
                UPDATE {$this->table}
                SET fecha_cierre = CURRENT_TIMESTAMP,
                    ventas_usd = :ventas_usd,
                    ventas_bs = :ventas_bs,
                    declarado_usd = :declarado_usd,
                    declarado_bs = :declarado_bs,
                    diferencia_usd = :diff_usd,
                    diferencia_bs = :diff_bs,
                    estado = 'cerrada'
                WHERE id = :id
                  AND estado = 'abierta'
                  AND user_id IN (SELECT id FROM users WHERE business_id = :business_id)
            ");
            $stmt->execute([
                'ventas_usd' => $ventas_usd,
                'ventas_bs' => $ventas_bs,
                'declarado_usd' => $declarado_usd,
                'declarado_bs' => $declarado_bs,
                'diff_usd' => $diff_usd,
                'diff_bs' => $diff_bs,
                'id' => $id,
                'business_id' => $businessId
            ]);
            if ($stmt->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            $this->logAudit('UPDATE', $id, [
                'action' => 'CIERRE_CAJA',
                'esperado_usd' => $esperado_usd,
                'declarado_usd' => $declarado_usd,
                'diferencia_usd' => $diff_usd
            ]);
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Historial de todas las sesiones con nombre de usuario
     */
    public function allSessionsWithUsers() {
        $business_id = (int)($_SESSION['business_id'] ?? 0);
        if ($business_id <= 0) return [];
        $stmt = $this->db->prepare("
            SELECT a.*, u.username 
            FROM {$this->table} a
            JOIN users u ON a.user_id = u.id
            WHERE u.business_id = ?
            ORDER BY a.fecha_apertura DESC
            LIMIT 50
        ");
        $stmt->execute([$business_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
