<?php
require_once __DIR__ . '/../../../core/Model.php';
require_once __DIR__ . '/../../../core/UnitConversionService.php';
require_once __DIR__ . '/../../../core/CostCalculationService.php';

class Recipe extends Model {
    protected $table = 'recipe_items';
    protected $tenantColumn = 'tenant_id';

    public function __construct() {
        parent::__construct();
        $this->ensureTable();
    }

    /**
     * Auto-Migración: garantiza la existencia de recipe_items
     * (compatible SQLite y PostgreSQL)
     */
    private function ensureTable() {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $autoInc = $driver === 'pgsql' ? 'SERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS recipe_items (
                id {$autoInc},
                tenant_id INTEGER,
                dish_id INTEGER NOT NULL,
                ingredient_id INTEGER NOT NULL,
                quantity REAL NOT NULL,
                unit_id INTEGER,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (dish_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (ingredient_id) REFERENCES products(id) ON DELETE CASCADE
            )");
        } catch (\Exception $e) { }
    }

    /**
     * Obtiene la receta completa de un plato con datos del ingrediente.
     */
    public function getForDish($dishId) {
        $sql = "SELECT ri.*, 
                       p.name as ingredient_name,
                       p.stock as ingredient_stock,
                       p.unit_cost as ingredient_cost,
                       p.base_unit_id as ingredient_unit_id,
                       u.name as unit_name,
                       u.abbreviation as unit_abbr,
                       u.conversion_to_base as unit_factor,
                       u2.conversion_to_base as sale_unit_factor
                FROM {$this->table} ri
                JOIN products p ON ri.ingredient_id = p.id
                LEFT JOIN units_of_measure u ON ri.unit_id = u.id
                LEFT JOIN units_of_measure u2 ON p.sale_unit_id = u2.id
                WHERE ri.dish_id = :dish_id";
        $params = ['dish_id' => $dishId];
        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $sql .= " AND ri.{$this->tenantColumn} = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }
        $sql .= " ORDER BY p.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda (reemplaza) la receta de un plato de forma transaccional.
     * $items = [ ['ingredient_id' => int, 'quantity' => float, 'unit_id' => int|null, 'notes' => string|null], ... ]
     */
    public function saveRecipe($dishId, $items) {
        // Validar que el plato exista y pertenezca al tenant actual
        $stmtCheck = $this->db->prepare("SELECT id FROM products WHERE id = :id AND is_dish = 1");
        $stmtCheck->execute(['id' => $dishId]);
        if (!$stmtCheck->fetch()) {
            throw new Exception("El plato no existe o no está marcado como plato.");
        }

        $this->db->beginTransaction();
        try {
            $stmtDel = $this->db->prepare("DELETE FROM {$this->table} WHERE dish_id = :dish_id");
            $stmtDel->execute(['dish_id' => $dishId]);

            $tenantId = $_SESSION['business_id'] ?? null;
            $stmtIns = $this->db->prepare("INSERT INTO {$this->table} (tenant_id, dish_id, ingredient_id, quantity, unit_id, notes)
                                           VALUES (:tenant_id, :dish_id, :ingredient_id, :quantity, :unit_id, :notes)");

            foreach ($items as $item) {
                $qty = (float)($item['quantity'] ?? 0);
                if ($qty <= 0 || empty($item['ingredient_id'])) continue;

                $stmtIns->execute([
                    'tenant_id'     => $tenantId,
                    'dish_id'       => $dishId,
                    'ingredient_id' => (int)$item['ingredient_id'],
                    'quantity'      => $qty,
                    'unit_id'       => !empty($item['unit_id']) ? (int)$item['unit_id'] : null,
                    'notes'         => $item['notes'] ?? null
                ]);
            }

            $this->logAudit('SAVE_RECIPE', $dishId, ['items_count' => count($items)]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * Convierte la cantidad de una línea de receta a unidades base del inventario
     * (stock se almacena en unidad base).
     */
    private function qtyInBaseUnits($quantity, $recipeUnitId) {
        if ($recipeUnitId) {
            try {
                return UnitConversionService::convertToBase($quantity, $recipeUnitId);
            } catch (Exception $e) {
                return (float)$quantity;
            }
        }
        return (float)$quantity;
    }

    /**
     * Calcula el costo total de preparar UN plato según su receta.
     */
    public function calculateCost($dishId) {
        $items = $this->getForDish($dishId);
        $total = 0.0;

        foreach ($items as $item) {
            $qty = (float)$item['quantity'];
            $ingredientCostStr = $item['ingredient_cost'] ?? 0;
            $saleUnitFactor = $item['sale_unit_factor'] ?? 1.0;
            
            $cost = \CostCalculationService::calculateIngredientCost($qty, $item['unit_id'], $ingredientCostStr, $saleUnitFactor);
            if ($cost === 'MISSING_COST') {
                $cost = 0;
            }
            $total += $cost; // No redondear antes de tiempo
        }
        return $total;
    }

    /**
     * ¿Cuántas porciones de este plato se pueden preparar con el stock actual?
     * Devuelve null si el plato no tiene receta (stock ilimitado / manejo manual).
     */
    public function getAvailableServings($dishId) {
        $items = $this->getForDish($dishId);
        if (empty($items)) return null;

        $servings = PHP_FLOAT_MAX;
        foreach ($items as $item) {
            $needPerDish = $this->qtyInBaseUnits((float)$item['quantity'], $item['unit_id']);
            if ($needPerDish <= 0) continue;
            $possible = ((float)($item['ingredient_stock'] ?? 0)) / $needPerDish;
            $servings = min($servings, floor($possible));
        }
        return $servings === PHP_FLOAT_MAX ? null : max(0, (int)$servings);
    }

    /**
     * Valida que haya suficientes ingredientes para N porciones.
     * Lanza Exception si falta stock.
     */
    public function checkAvailability($dishId, $servings) {
        $items = $this->getForDish($dishId);
        foreach ($items as $item) {
            $need = $this->qtyInBaseUnits((float)$item['quantity'] * $servings, $item['unit_id']);
            $stock = (float)($item['ingredient_stock'] ?? 0);
            if ($need > $stock) {
                throw new Exception(
                    "Stock insuficiente de '{$item['ingredient_name']}' para preparar el plato. " .
                    "Necesitas " . round($need, 3) . " y hay " . round($stock, 3) . "."
                );
            }
        }
        return true;
    }

    /**
     * Descuenta los ingredientes del inventario al vender un plato.
     * Debe ejecutarse DENTRO de la transacción de la venta (misma conexión PDO).
     * No recursivo: los ingredientes deben ser insumos, no otros platos.
     */
    public function consumeIngredients($dishId, $servings, $referenceType, $referenceId, $userId) {
        $items = $this->getForDish($dishId);

        $stmtUpdate = $this->db->prepare("UPDATE products SET stock = stock - :qty WHERE id = :pid");
        $stmtAfter  = $this->db->prepare("SELECT stock FROM products WHERE id = :pid");

        foreach ($items as $item) {
            $need = $this->qtyInBaseUnits((float)$item['quantity'] * $servings, $item['unit_id']);
            if ($need <= 0) continue;

            $stmtUpdate->execute(['qty' => $need, 'pid' => $item['ingredient_id']]);
            $stmtAfter->execute(['pid' => $item['ingredient_id']]);
            $stockAfter = $stmtAfter->fetchColumn();
        }
        return true;
    }

    /**
     * Restaura los ingredientes al anular una venta de platos.
     * Debe ejecutarse DENTRO de la transacción de anulación (misma conexión PDO).
     */
    public function restoreIngredients($dishId, $servings, $referenceType, $referenceId, $userId) {
        $items = $this->getForDish($dishId);

        $stmtUpdate = $this->db->prepare("UPDATE products SET stock = stock + :qty WHERE id = :pid");
        $stmtAfter  = $this->db->prepare("SELECT stock FROM products WHERE id = :pid");

        foreach ($items as $item) {
            $restore = $this->qtyInBaseUnits((float)$item['quantity'] * $servings, $item['unit_id']);
            if ($restore <= 0) continue;

            $stmtUpdate->execute(['qty' => $restore, 'pid' => $item['ingredient_id']]);
            $stmtAfter->execute(['pid' => $item['ingredient_id']]);
            $stockAfter = $stmtAfter->fetchColumn();
        }
        return true;
    }
}
