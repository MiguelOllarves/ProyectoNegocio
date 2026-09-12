<?php
/**
 * InventoryConsumptionService - Servicio central de consumo de inventario.
 * 
 * POS y Storefront deben usar el MISMO motor de consumo.
 * Maneja: stock directo, recetas (dishes), y configuraciones (options).
 */
require_once __DIR__ . '/UnitConversionService.php';
require_once __DIR__ . '/../modules/restaurant/models/Recipe.php';

class InventoryConsumptionService {

    private $db;
    private $tenantId;

    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
        $this->tenantId = $_SESSION['business_id'] ?? null;
    }

    /**
     * Verifica disponibilidad de un producto (simple o plato elaborado).
     * Retorna ['available' => bool, 'max_qty' => float, 'reason' => string]
     */
    public function checkAvailability(int $productId, float $quantity = 1): array {
        $stmt = $this->db->prepare("SELECT id, stock, is_dish, allow_fractional_sales, measurement_type FROM products WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$productId, $this->tenantId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            return ['available' => false, 'max_qty' => 0, 'reason' => 'Producto no encontrado.'];
        }

        if (!empty($product['is_dish'])) {
            return $this->checkDishAvailability($productId, $quantity);
        }

        if ($product['stock'] < $quantity) {
            return [
                'available' => false,
                'max_qty' => $product['stock'],
                'reason' => "Stock insuficiente. Disponible: {$product['stock']}"
            ];
        }

        return ['available' => true, 'max_qty' => $product['stock'], 'reason' => ''];
    }

    /**
     * Verifica disponibilidad de un plato elaborado (por su receta).
     */
    private function checkDishAvailability(int $dishId, float $quantity): array {
        $recipeModel = new Recipe();
        $items = $recipeModel->getForDish($dishId);

        if (empty($items)) {
            return ['available' => false, 'max_qty' => 0, 'reason' => 'El plato no tiene receta configurada.'];
        }

        $maxServings = PHP_FLOAT_MAX;

        foreach ($items as $item) {
            $need = $recipeModel->qtyInBaseUnits((float)$item['quantity'], $item['unit_id']);
            if ($need <= 0) continue;

            $stmt = $this->db->prepare("SELECT stock FROM products WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$item['ingredient_id'], $this->tenantId]);
            $stock = (float)$stmt->fetchColumn();

            $availableForItem = floor($stock / $need);
            if ($availableForItem < $maxServings) {
                $maxServings = $availableForItem;
            }
        }

        if ($quantity > $maxServings) {
            return [
                'available' => false,
                'max_qty' => $maxServings,
                'reason' => "Solo hay ingredientes para {$maxServings} porciones."
            ];
        }

        return ['available' => true, 'max_qty' => $maxServings, 'reason' => ''];
    }

    /**
     * Consume un producto del inventario.
     * Para platos: descuenta ingredientes de la receta.
     * Para productos simples: descuenta stock directamente.
     * Registra en Kardex.
     */
    public function consume(int $productId, float $quantity, string $refType, int $refId, int $userId, float $priceAtSale = 0): bool {
        $stmt = $this->db->prepare("SELECT id, stock, is_dish, sale_unit_id, unit_cost FROM products WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$productId, $this->tenantId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) throw new \Exception("Producto no encontrado.");

        if (!empty($product['is_dish'])) {
            $recipeModel = new Recipe();
            $recipeModel->consumeIngredients($productId, $quantity, $refType, $refId, $userId);
        } else {
            $qtyInBase = $quantity;
            if (!empty($product['sale_unit_id'])) {
                try {
                    $qtyInBase = UnitConversionService::convertToBase($quantity, $product['sale_unit_id']);
                } catch (\Exception $e) {
                    $qtyInBase = $quantity;
                }
            }

            $stmtUpdate = $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND tenant_id = ?");
            $stmtUpdate->execute([$qtyInBase, $productId, $this->tenantId]);

            $stmtAfter = $this->db->prepare("SELECT stock FROM products WHERE id = ? AND tenant_id = ?");
            $stmtAfter->execute([$productId, $this->tenantId]);
            $stockAfter = $stmtAfter->fetchColumn();

            $stmtKardex = $this->db->prepare("INSERT INTO kardex (product_id, type, quantity, stock_after, reference_type, reference_id, user_id) VALUES (?, 'salida_venta', ?, ?, ?, ?, ?)");
            $stmtKardex->execute([$productId, $qtyInBase, $stockAfter, $refType, $refId, $userId]);
        }

        return true;
    }

    /**
     * Restaura un producto al inventario (al anular una venta).
     */
    public function restore(int $productId, float $quantity, string $refType, int $refId, int $userId): bool {
        $stmt = $this->db->prepare("SELECT id, stock, is_dish, sale_unit_id FROM products WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$productId, $this->tenantId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) throw new \Exception("Producto no encontrado.");

        if (!empty($product['is_dish'])) {
            $recipeModel = new Recipe();
            $recipeModel->restoreIngredients($productId, $quantity, $refType, $refId, $userId);
        } else {
            $qtyInBase = $quantity;
            if (!empty($product['sale_unit_id'])) {
                try {
                    $qtyInBase = UnitConversionService::convertToBase($quantity, $product['sale_unit_id']);
                } catch (\Exception $e) {
                    $qtyInBase = $quantity;
                }
            }

            $stmtUpdate = $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = ? AND tenant_id = ?");
            $stmtUpdate->execute([$qtyInBase, $productId, $this->tenantId]);

            $stmtAfter = $this->db->prepare("SELECT stock FROM products WHERE id = ? AND tenant_id = ?");
            $stmtAfter->execute([$productId, $this->tenantId]);
            $stockAfter = $stmtAfter->fetchColumn();

            $stmtKardex = $this->db->prepare("INSERT INTO kardex (product_id, type, quantity, stock_after, reference_type, reference_id, user_id) VALUES (?, 'entrada_anulacion', ?, ?, ?, ?, ?)");
            $stmtKardex->execute([$productId, $qtyInBase, $stockAfter, $refType, $refId, $userId]);
        }

        return true;
    }

    /**
     * Calcula el costo de un producto (simple o plato elaborado).
     */
    public function calculateCost(int $productId): float {
        $stmt = $this->db->prepare("SELECT is_dish, unit_cost FROM products WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$productId, $this->tenantId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) return 0;

        if (!empty($product['is_dish'])) {
            $recipeModel = new Recipe();
            return $recipeModel->calculateCost($productId);
        }

        return (float)($product['unit_cost'] ?? 0);
    }
}
