<?php
require_once __DIR__ . '/../../../core/Model.php';

class Purchase extends Model {
    protected $table = 'purchases';

    /**
     * Crea una compra completa con sus ítems, actualiza stock y registra en Kardex.
     * Usa transacciones para garantizar integridad.
     */
    public function createWithItems($userId, $supplierId, $items, $notes = '') {
        $dbInstance = \Database::getInstance();
        $dbInstance->beginTransaction();

        try {
            $total = 0;
            foreach ($items as $item) {
                $total += $item['quantity'] * $item['cost'];
            }

            // 1. Crear cabecera de compra
            $this->db->prepare("INSERT INTO purchases (supplier_id, user_id, total, notes) VALUES (?, ?, ?, ?)")
                ->execute([$supplierId, $userId, $total, $notes]);
            $purchaseId = $this->db->lastInsertId();

            // 2. Insertar ítems + actualizar stock + kardex
            $stmtItem = $this->db->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, cost_per_unit) VALUES (?, ?, ?, ?)");
            $stmtStock = $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmtStockAfter = $this->db->prepare("SELECT stock FROM products WHERE id = ?");
            $stmtKardex = $this->db->prepare("INSERT INTO kardex (product_id, type, quantity, stock_after, reference_type, reference_id, note, user_id) VALUES (?, 'entrada_compra', ?, ?, 'purchase', ?, ?, ?)");

            foreach ($items as $item) {
                $stmtItem->execute([$purchaseId, $item['product_id'], $item['quantity'], $item['cost']]);
                $stmtStock->execute([$item['quantity'], $item['product_id']]);

                $stmtStockAfter->execute([$item['product_id']]);
                $stockAfter = $stmtStockAfter->fetchColumn();

                $stmtKardex->execute([
                    $item['product_id'],
                    $item['quantity'],
                    $stockAfter,
                    $purchaseId,
                    'Compra #' . $purchaseId,
                    $userId
                ]);
            }

            $dbInstance->commit();
            return $purchaseId;
        } catch (\Exception $e) {
            $dbInstance->rollback();
            return false;
        }
    }

    public function allWithSupplier() {
        $sql = "SELECT p.*, s.name as supplier_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id ORDER BY p.id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
