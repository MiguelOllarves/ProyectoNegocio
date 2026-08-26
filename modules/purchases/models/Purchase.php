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
            $stmtItem = $this->db->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_type, cost_per_unit) VALUES (?, ?, ?, ?, ?)");
            $stmtStock = $this->db->prepare("UPDATE products SET stock = stock + ?, unit_cost = ?, bulk_cost = ? WHERE id = ?");
            $stmtStockAfter = $this->db->prepare("SELECT stock FROM products WHERE id = ?");
            $stmtKardex = $this->db->prepare("INSERT INTO kardex (product_id, type, quantity, stock_after, reference_type, reference_id, note, user_id) VALUES (?, 'entrada_compra', ?, ?, 'purchase', ?, ?, ?)");
            $stmtProdConf = $this->db->prepare("SELECT purchase_unit_id FROM products WHERE id = ?");
            
            require_once __DIR__ . '/../../../core/CostCalculationService.php';
            require_once __DIR__ . '/../../../core/UnitConversionService.php';

            foreach ($items as $item) {
                if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                    throw new \Exception("Cantidad inválida para compra ({$item['quantity']}).");
                }
                
                // Ensure unit_type exists
                $unitType = $item['unit_type'] ?? 'unidad';

                $stmtProdConf->execute([$item['product_id']]);
                $purchaseUnitId = $stmtProdConf->fetchColumn();
                if (!$purchaseUnitId) {
                    throw new \Exception("El producto no tiene unidad de compra configurada. Obligatorio por el motor de inventario (ID {$item['product_id']}).");
                }

                $totalItemCost = $item['quantity'] * $item['cost'];
                $quantityInBaseUnits = \UnitConversionService::convertToBase($item['quantity'], $purchaseUnitId);
                $costPerBaseUnit = \CostCalculationService::calculateCostPerBaseUnit($totalItemCost, $item['quantity'], $purchaseUnitId);

                $stmtItem->execute([$purchaseId, $item['product_id'], $item['quantity'], $unitType, $item['cost']]);
                $stmtStock->execute([$quantityInBaseUnits, $costPerBaseUnit, $item['cost'], $item['product_id']]);

                $stmtStockAfter->execute([$item['product_id']]);
                $stockAfter = $stmtStockAfter->fetchColumn();

                $stmtKardex->execute([
                    $item['product_id'],
                    $quantityInBaseUnits,
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
            throw $e; // Throw exception to be caught in Controller
        }
    }

    public function allWithSupplier() {
        $business_id = $_SESSION['business_id'] ?? 1;
        $sql = "SELECT p.*, s.name as supplier_name FROM purchases p LEFT JOIN suppliers s ON p.supplier_id = s.id JOIN users u ON p.user_id = u.id WHERE u.business_id = ? ORDER BY p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$business_id]);
        return $stmt->fetchAll();
    }

    public function findWithItems($id, $business_id) {
        // Verify it belongs to the tenant
        $sql = "SELECT p.*, s.name as supplier_name, s.contact_name as supplier_contact, s.phone as supplier_phone, s.address as supplier_address, u.full_name as user_name 
                FROM purchases p 
                LEFT JOIN suppliers s ON p.supplier_id = s.id 
                JOIN users u ON p.user_id = u.id 
                WHERE p.id = ? AND u.business_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $business_id]);
        $purchase = $stmt->fetch();

        if ($purchase) {
            $stmtItems = $this->db->prepare("
                SELECT pi.*, pr.name, pr.sku 
                FROM purchase_items pi 
                JOIN products pr ON pi.product_id = pr.id 
                WHERE pi.purchase_id = ?
            ");
            $stmtItems->execute([$id]);
            $purchase['items'] = $stmtItems->fetchAll();
            return $purchase;
        }
        return false;
    }

    public function deleteWithReversal($id, $userId, $business_id) {
        $purchase = $this->findWithItems($id, $business_id);
        if (!$purchase) return false;

        $dbInstance = \Database::getInstance();
        $dbInstance->beginTransaction();

        try {
            $stmtStock = $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmtStockAfter = $this->db->prepare("SELECT stock FROM products WHERE id = ?");
            $stmtKardex = $this->db->prepare("INSERT INTO kardex (product_id, type, quantity, stock_after, reference_type, reference_id, note, user_id) VALUES (?, 'salida_anulacion', ?, ?, 'purchase', ?, ?, ?)");
            $stmtProdConf = $this->db->prepare("SELECT purchase_unit_id FROM products WHERE id = ?");
            require_once __DIR__ . '/../../../core/UnitConversionService.php';

            foreach ($purchase['items'] as $item) {
                $stmtProdConf->execute([$item['product_id']]);
                $purchaseUnitId = $stmtProdConf->fetchColumn();
                if (!$purchaseUnitId) throw new \Exception("Producto sin unidad de compra detectado al revertir.");

                $quantityInBaseUnits = \UnitConversionService::convertToBase($item['quantity'], $purchaseUnitId);

                // Reverse the stock correctly in base units
                $stmtStock->execute([$quantityInBaseUnits, $item['product_id']]);
                
                // Get stock after
                $stmtStockAfter->execute([$item['product_id']]);
                $stockAfter = $stmtStockAfter->fetchColumn();

                // Log Kardex
                $stmtKardex->execute([
                    $item['product_id'],
                    $item['quantity'],
                    $stockAfter,
                    $id,
                    'Anulación Compra #' . $id,
                    $userId
                ]);
            }

            // Finally, cascade delete
            $stmtDel = $this->db->prepare("DELETE FROM purchases WHERE id = ?");
            $stmtDel->execute([$id]);

            $dbInstance->commit();
            return true;
        } catch (\Exception $e) {
            $dbInstance->rollback();
            throw $e;
        }
    }

    public function updateWithItems($id, $userId, $supplierId, $items, $notes, $business_id) {
        // We will reverse the old purchase and apply the new items
        $purchase = $this->findWithItems($id, $business_id);
        if (!$purchase) return false;

        $dbInstance = \Database::getInstance();
        $dbInstance->beginTransaction();

        try {
            // REVERSE OLD ITEMS
            $stmtStockSub = $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmtStockAfter = $this->db->prepare("SELECT stock FROM products WHERE id = ?");
            $stmtKardexRev = $this->db->prepare("INSERT INTO kardex (product_id, type, quantity, stock_after, reference_type, reference_id, note, user_id) VALUES (?, 'ajuste_negativo', ?, ?, 'purchase_edit', ?, ?, ?)");
            $stmtProdConfRev = $this->db->prepare("SELECT purchase_unit_id FROM products WHERE id = ?");
            require_once __DIR__ . '/../../../core/UnitConversionService.php';
            require_once __DIR__ . '/../../../core/CostCalculationService.php';

            foreach ($purchase['items'] as $oldItem) {
                $stmtProdConfRev->execute([$oldItem['product_id']]);
                $purchaseUnitId = $stmtProdConfRev->fetchColumn();
                if (!$purchaseUnitId) throw new \Exception("Producto sin unidad de compra detectado al revertir edición.");

                $quantityInBaseUnits = \UnitConversionService::convertToBase($oldItem['quantity'], $purchaseUnitId);

                $stmtStockSub->execute([$quantityInBaseUnits, $oldItem['product_id']]);
                $stmtStockAfter->execute([$oldItem['product_id']]);
                $stockAfter = $stmtStockAfter->fetchColumn();
                $stmtKardexRev->execute([$oldItem['product_id'], $oldItem['quantity'], $stockAfter, $id, 'Reversión edición compra #' . $id, $userId]);
            }

            // DELETE OLD ITEMS
            $this->db->prepare("DELETE FROM purchase_items WHERE purchase_id = ?")->execute([$id]);

            // INSERT NEW ITEMS
            $total = 0;
            $stmtItem = $this->db->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_type, cost_per_unit) VALUES (?, ?, ?, ?, ?)");
            $stmtStockAdd = $this->db->prepare("UPDATE products SET stock = stock + ?, unit_cost = ?, bulk_cost = ? WHERE id = ?");
            $stmtKardexAdd = $this->db->prepare("INSERT INTO kardex (product_id, type, quantity, stock_after, reference_type, reference_id, note, user_id) VALUES (?, 'entrada_compra', ?, ?, 'purchase_edit', ?, ?, ?)");
            $stmtProdConf = $this->db->prepare("SELECT purchase_unit_id FROM products WHERE id = ?");

            foreach ($items as $item) {
                if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                    throw new \Exception("Cantidad de edición inválida ({$item['quantity']}).");
                }

                $total += $item['quantity'] * $item['cost'];
                $unitType = $item['unit_type'] ?? 'unidad';

                $stmtProdConf->execute([$item['product_id']]);
                $purchaseUnitId = $stmtProdConf->fetchColumn();
                if (!$purchaseUnitId) {
                    throw new \Exception("El producto no tiene unidad de compra configurada.");
                }

                $totalItemCost = $item['quantity'] * $item['cost'];
                $quantityInBaseUnits = \UnitConversionService::convertToBase($item['quantity'], $purchaseUnitId);
                $costPerBaseUnit = \CostCalculationService::calculateCostPerBaseUnit($totalItemCost, $item['quantity'], $purchaseUnitId);

                $stmtItem->execute([$id, $item['product_id'], $item['quantity'], $unitType, $item['cost']]);
                
                // Add stock
                $stmtStockAdd->execute([$quantityInBaseUnits, $costPerBaseUnit, $item['cost'], $item['product_id']]);
                $stmtStockAfter->execute([$item['product_id']]);
                $stockAfter = $stmtStockAfter->fetchColumn();

                $stmtKardexAdd->execute([$item['product_id'], $quantityInBaseUnits, $stockAfter, $id, 'Edición Compra #' . $id, $userId]);
            }

            // UPDATE HEADER
            $this->db->prepare("UPDATE purchases SET supplier_id = ?, user_id = ?, total = ?, notes = ? WHERE id = ?")
                     ->execute([$supplierId, $userId, $total, $notes, $id]);

            $dbInstance->commit();
            return true;
        } catch (\Exception $e) {
            $dbInstance->rollback();
            throw $e;
        }
    }
}
