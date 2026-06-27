<?php
require_once __DIR__ . '/../../../core/Model.php';

class Sale extends Model {
    protected $table = 'sales';

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

            $stmtItem = $this->db->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price_at_sale) VALUES (:sid, :pid, :qty, :price)");
            $stmtUpdateStock = $this->db->prepare("UPDATE products SET stock = stock - :qty WHERE id = :pid");

            $stmtKardex = $this->db->prepare("INSERT INTO kardex (product_id, type, quantity, stock_after, reference_type, reference_id, user_id) VALUES (:pid, 'salida_venta', :qty, :stock_after, 'sale', :sid, :uid)");
            $stmtStockAfter = $this->db->prepare("SELECT stock FROM products WHERE id = :pid");

            foreach ($items as $item) {
                // Guardar el item de venta
                $stmtItem->execute([
                    'sid' => $saleId,
                    'pid' => $item['id'],
                    'qty' => $item['quantity'],
                    'price' => $item['price']
                ]);

                // Descontar inventario
                $stmtUpdateStock->execute([
                    'qty' => $item['quantity'],
                    'pid' => $item['id']
                ]);

                $stmtStockAfter->execute(['pid' => $item['id']]);
                $stockAfter = $stmtStockAfter->fetchColumn();

                $stmtKardex->execute([
                    'pid' => $item['id'],
                    'qty' => $item['quantity'],
                    'stock_after' => $stockAfter,
                    'sid' => $saleId,
                    'uid' => $userId
                ]);
            }
            $this->db->commit();
            return $saleId;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
