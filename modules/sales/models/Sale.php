<?php
require_once __DIR__ . '/../../../core/Model.php';
require_once __DIR__ . '/../../restaurant/models/Recipe.php';

class Sale extends Model {
    protected $table = 'sales';

    public function __construct() {
        parent::__construct();
        // Asegurar esquema extendido de ventas
        try { $this->db->exec("ALTER TABLE sales ADD COLUMN subtotal REAL DEFAULT 0"); } catch (\Exception $e) {}
        try { $this->db->exec("ALTER TABLE sales ADD COLUMN iva REAL DEFAULT 0"); } catch (\Exception $e) {}
        try { $this->db->exec("ALTER TABLE sales ADD COLUMN igtf REAL DEFAULT 0"); } catch (\Exception $e) {}
        
        $autoInc = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 'SERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        
        $this->db->exec("CREATE TABLE IF NOT EXISTS ventas_pagos (
            id {$autoInc},
            venta_id INTEGER,
            metodo_pago TEXT,
            monto_divisa REAL,
            monto_bs REAL,
            tasa_aplicada REAL,
            FOREIGN KEY (venta_id) REFERENCES sales(id) ON DELETE CASCADE
        )");
        
        try { $this->db->exec("ALTER TABLE sales ADD COLUMN status VARCHAR(20) DEFAULT 'completada'"); } catch (\Exception $e) {}
    }

    public function getDailySales($business_id) {
        // Obtenemos la fecha actual desde PHP para asegurar compatibilidad universal entre SQLite y PostgreSQL
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');
        
        $stmt = $this->db->prepare("
            SELECT s.*, u.username as cashier 
            FROM sales s 
            JOIN users u ON s.user_id = u.id 
            WHERE u.business_id = ? 
              AND s.created_at >= ? AND s.created_at <= ?
            ORDER BY s.id DESC
        ");
        $stmt->execute([$business_id, $todayStart, $todayEnd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function voidSale($saleId, $userId) {
        $this->db->beginTransaction();
        try {
            // Check current status
            $stmt = $this->db->prepare("SELECT status FROM sales WHERE id = ?");
            $stmt->execute([$saleId]);
            $status = $stmt->fetchColumn();
            
            if ($status === 'anulada') {
                throw new Exception("La venta ya ha sido anulada.");
            }

            // Get items to restore stock
            $stmtItems = $this->db->prepare("SELECT si.product_id, si.quantity, p.is_dish, p.sale_unit_id 
                                             FROM sale_items si 
                                             JOIN products p ON si.product_id = p.id
                                             WHERE sale_id = ?");
            $stmtItems->execute([$saleId]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            $recipeModel = new Recipe();
            $stmtRestoreStock = $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmtKardex = $this->db->prepare("INSERT INTO kardex (product_id, type, quantity, stock_after, reference_type, reference_id, user_id) VALUES (?, 'entrada_anulacion', ?, ?, 'sale_void', ?, ?)");
            $stmtStockAfter = $this->db->prepare("SELECT stock FROM products WHERE id = ?");

            foreach ($items as $item) {
                if (!empty($item['is_dish'])) {
                    // Plato: restaurar los ingredientes de su receta
                    $recipeModel->restoreIngredients(
                        $item['product_id'],
                        (float)$item['quantity'],
                        'sale_void',
                        $saleId,
                        $userId
                    );
                    continue;
                }
                
                // Restore stock in base units
                require_once __DIR__ . '/../../../core/UnitConversionService.php';
                $restoreQty = $item['quantity'];
                if (!empty($item['sale_unit_id'])) {
                    try {
                        $restoreQty = \UnitConversionService::convertToBase($item['quantity'], $item['sale_unit_id']);
                    } catch (Exception $e) {
                        $restoreQty = $item['quantity']; // Fallback
                    }
                }

                $stmtRestoreStock->execute([$restoreQty, $item['product_id']]);
                
                // Get new stock
                $stmtStockAfter->execute([$item['product_id']]);
                $stockAfter = $stmtStockAfter->fetchColumn();

                // Log Kardex
                $stmtKardex->execute([
                    $item['product_id'],
                    $restoreQty,
                    $stockAfter,
                    $saleId,
                    $userId
                ]);
            }

            // Mark sale as voided
            $stmtVoid = $this->db->prepare("UPDATE sales SET status = 'anulada' WHERE id = ?");
            $stmtVoid->execute([$saleId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function createSale($userId, $subtotal, $iva, $igtf, $total, $cashReceived, $changeGiven, $items, $payments) {
        $this->db->beginTransaction();
        try {
            $saleData = [
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'iva' => $iva,
                'igtf' => $igtf,
                'total' => $total,
                'cash_received' => $cashReceived,
                'change_given' => $changeGiven
            ];
            $saleId = $this->create($saleData);
            if (!$saleId) throw new Exception("Error al crear encabezado de venta");

            // Ingresar los pagos mixtos
            $stmtPago = $this->db->prepare("INSERT INTO ventas_pagos (venta_id, metodo_pago, monto_divisa, monto_bs, tasa_aplicada) VALUES (?, ?, ?, ?, ?)");
            foreach ($payments as $p) {
                // frontend sends 'amountUsd' and 'amountVes'
                $usd = $p['amountUsd'] ?? 0;
                $bs = $p['amountVes'] ?? 0;
                $rate = $usd > 0 ? ($bs / $usd) : 0; // approximate rate from the payment
                $stmtPago->execute([$saleId, $p['method'], $usd, $bs, $rate]);
            }

            $stmtItem = $this->db->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price_at_sale, cost_at_sale) VALUES (:sid, :pid, :qty, :price, :cost)");
            $stmtUpdateStock = $this->db->prepare("UPDATE products SET stock = stock - :qty WHERE id = :pid");

            $stmtKardex = $this->db->prepare("INSERT INTO kardex (product_id, type, quantity, stock_after, reference_type, reference_id, user_id) VALUES (:pid, 'salida_venta', :qty, :stock_after, 'sale', :sid, :uid)");
            $stmtStockAfter = $this->db->prepare("SELECT stock FROM products WHERE id = :pid");
            $stmtCost = $this->db->prepare("SELECT unit_cost FROM products WHERE id = :pid");

            $stmtCheckProduct = $this->db->prepare("SELECT stock, allow_fractional_sales, measurement_type, is_dish FROM products WHERE id = :pid");
            $recipeModel = new Recipe();

            foreach ($items as $item) {
                // Ensure floating point values for fractional quantities (e.g. 0.250kg)
                $actualQty = (float)($item['quantity'] ?? $item['qty'] ?? 1);
                
                if ($actualQty <= 0) {
                    throw new Exception("Cantidad de venta inválida ($actualQty).");
                }

                $stmtCheckProduct->execute(['pid' => $item['id']]);
                $productDb = $stmtCheckProduct->fetch(PDO::FETCH_ASSOC);
                
                if (!$productDb) {
                    throw new Exception("Producto ID: " . $item['id'] . " no encontrado.");
                }

                if (empty($productDb['allow_fractional_sales']) && floor($actualQty) != $actualQty) {
                    if (!in_array($productDb['measurement_type'], ['peso', 'volumen'])) {
                        throw new Exception("El producto ID: " . $item['id'] . " no permite cantidades fraccionadas.");
                    }
                }

                $factor = (float)($item['sale_unit_factor'] ?? 1);
                $qtyInBaseUnits = $actualQty * $factor;

                $isDish = !empty($productDb['is_dish']);

                if ($isDish) {
                    // Plato elaborado: el stock se descuenta de los INGREDIENTES de la receta,
                    // no del producto final. Validamos disponibilidad de insumos.
                    $recipeModel->checkAvailability($item['id'], $actualQty);
                    $unitCost = $recipeModel->calculateCost($item['id']);
                } else {
                    $currentStock = $productDb['stock'];
                    // Compare currentStock (in base units) with qtyInBaseUnits
                    if ($currentStock < $qtyInBaseUnits) {
                        throw new Exception("Stock insuficiente para el producto ID: " . $item['id']);
                    }

                    $stmtCost->execute(['pid' => $item['id']]);
                    $unitCost = $stmtCost->fetchColumn();
                    $unitCost = $unitCost ? (float)$unitCost : 0.0;
                }

                // Guardar el item de venta
                $stmtItem->execute([
                    'sid' => $saleId,
                    'pid' => $item['id'],
                    'qty' => $actualQty,
                    'price' => $item['price'],
                    'cost' => $unitCost
                ]);

                if ($isDish) {
                    // Descontar ingredientes de la receta (Kardex por insumo)
                    $recipeModel->consumeIngredients($item['id'], $actualQty, 'sale', $saleId, $userId);
                } else {
                    // Descontar inventario (en unidades base)
                    $stmtUpdateStock->execute([
                        'qty' => $qtyInBaseUnits,
                        'pid' => $item['id']
                    ]);

                    $stmtStockAfter->execute(['pid' => $item['id']]);
                    $stockAfter = $stmtStockAfter->fetchColumn();

                    $stmtKardex->execute([
                        'pid' => $item['id'],
                        'qty' => $qtyInBaseUnits,
                        'stock_after' => $stockAfter,
                        'sid' => $saleId,
                        'uid' => $userId
                    ]);
                }
            }
            $this->db->commit();
            return $saleId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
