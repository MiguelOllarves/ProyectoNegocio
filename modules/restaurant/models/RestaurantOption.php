<?php
require_once __DIR__ . '/../../../core/Model.php';

/**
 * RestaurantOption - Gestiona grupos de opciones y opciones para platos configurables.
 * Ejemplo: Milanesa con grupo "Contornos" (min=2, max=2) y opciones [Arroz, Pasta, Tajadas].
 */
class RestaurantOption extends Model {

    /**
     * Obtiene todos los grupos de opciones de un plato con sus opciones.
     */
    public function getGroupsForDish(int $dishId): array {
        $tenantId = $_SESSION['business_id'] ?? null;
        
        $stmtGroups = $this->db->prepare("
            SELECT * FROM restaurant_option_groups 
            WHERE product_id = ? AND tenant_id = ? AND active = TRUE
            ORDER BY display_order ASC, id ASC
        ");
        $stmtGroups->execute([$dishId, $tenantId]);
        $groups = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);

        if (empty($groups)) return [];

        $stmtOptions = $this->db->prepare("
            SELECT ro.*, p.name as product_name, p.stock, p.is_dish, p.price
            FROM restaurant_options ro
            JOIN products p ON ro.product_id = p.id
            WHERE ro.group_id = ? AND ro.tenant_id = ? AND ro.active = TRUE
            ORDER BY ro.display_order ASC, ro.id ASC
        ");

        foreach ($groups as &$group) {
            $stmtOptions->execute([$group['id'], $tenantId]);
            $group['options'] = $stmtOptions->fetchAll(PDO::FETCH_ASSOC);
        }

        return $groups;
    }

    /**
     * Crea un grupo de opciones para un plato.
     * Valida min/max selections.
     */
    public function createGroup(int $dishId, string $name, int $minSel, int $maxSel, bool $required = true, int $order = 0): int {
        if ($minSel < 0) throw new \Exception("min_selections no puede ser negativo.");
        if ($maxSel < 1) throw new \Exception("max_selections debe ser al menos 1.");
        if ($minSel > $maxSel) throw new \Exception("min_selections no puede ser mayor que max_selections.");

        $tenantId = $_SESSION['business_id'] ?? null;

        // Verificar que el plato pertenece al tenant
        $stmtCheck = $this->db->prepare("SELECT id FROM products WHERE id = ? AND tenant_id = ? AND is_dish = TRUE");
        $stmtCheck->execute([$dishId, $tenantId]);
        if (!$stmtCheck->fetch()) {
            throw new \Exception("El plato no existe o no pertenece a este negocio.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO restaurant_option_groups (tenant_id, product_id, name, min_selections, max_selections, required, display_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tenantId, $dishId, $name, $minSel, $maxSel, $required ? 1 : 0, $order]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Agrega una opción a un grupo.
     * Valida que: el grupo pertenece al tenant, el producto existe,
     * el producto no sea el mismo plato padre, y no haya duplicados.
     */
    public function addOption(int $groupId, int $productId, float $priceDelta = 0, int $order = 0): int {
        $tenantId = $_SESSION['business_id'] ?? null;

        // 1. Verificar que el grupo pertenece al tenant
        $stmtGroup = $this->db->prepare("SELECT product_id FROM restaurant_option_groups WHERE id = ? AND tenant_id = ?");
        $stmtGroup->execute([$groupId, $tenantId]);
        $group = $stmtGroup->fetch(PDO::FETCH_ASSOC);
        if (!$group) {
            throw new \Exception("El grupo de opciones no existe o no pertenece a este negocio.");
        }

        // 2. Verificar que el producto pertenece al tenant
        $stmtProd = $this->db->prepare("SELECT id, is_dish FROM products WHERE id = ? AND tenant_id = ?");
        $stmtProd->execute([$productId, $tenantId]);
        $product = $stmtProd->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new \Exception("El producto seleccionado no existe o no pertenece a este negocio.");
        }

        // 3. Impedir que el plato padre sea su propia opción
        if ((int)$group['product_id'] === $productId) {
            throw new \Exception("Un plato no puede ser opción de sí mismo.");
        }

        // 4. Impedir duplicados (mismo producto en mismo grupo)
        $stmtDup = $this->db->prepare("SELECT id FROM restaurant_options WHERE group_id = ? AND product_id = ? AND tenant_id = ?");
        $stmtDup->execute([$groupId, $productId, $tenantId]);
        if ($stmtDup->fetch()) {
            throw new \Exception("Este producto ya es opción en este grupo.");
        }

        // 5. Validar price_delta (no negativo)
        if ($priceDelta < 0) {
            throw new \Exception("El precio adicional no puede ser negativo.");
        }

        $stmt = $this->db->prepare("
            INSERT INTO restaurant_options (tenant_id, group_id, product_id, price_delta, display_order)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tenantId, $groupId, $productId, $priceDelta, $order]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Elimina un grupo y todas sus opciones (cascade).
     */
    public function deleteGroup(int $groupId): bool {
        $tenantId = $_SESSION['business_id'] ?? null;
        $stmt = $this->db->prepare("DELETE FROM restaurant_option_groups WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$groupId, $tenantId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Elimina una opción específica.
     */
    public function deleteOption(int $optionId): bool {
        $tenantId = $_SESSION['business_id'] ?? null;
        $stmt = $this->db->prepare("DELETE FROM restaurant_options WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$optionId, $tenantId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Valida una configuración de opciones enviada desde el frontend.
     * Retorna ['valid' => bool, 'price_delta' => float, 'error' => string]
     */
    public function validateConfiguration(int $dishId, array $selectedOptions): array {
        $groups = $this->getGroupsForDish($dishId);
        if (empty($groups)) {
            return ['valid' => true, 'price_delta' => 0, 'error' => ''];
        }

        $totalDelta = 0;
        $selectedByGroup = [];

        foreach ($selectedOptions as $opt) {
            $groupId = $opt['group_id'] ?? 0;
            $optionId = $opt['option_id'] ?? 0;
            if (!isset($selectedByGroup[$groupId])) $selectedByGroup[$groupId] = [];
            $selectedByGroup[$groupId][] = $optionId;
        }

        foreach ($groups as $group) {
            $gid = $group['id'];
            $selected = $selectedByGroup[$gid] ?? [];
            $count = count($selected);

            if ($group['required'] && $count < $group['min_selections']) {
                return [
                    'valid' => false,
                    'price_delta' => 0,
                    'error' => "El grupo '{$group['name']}' requiere al menos {$group['min_selections']} opción(es)."
                ];
            }

            if ($count > $group['max_selections']) {
                return [
                    'valid' => false,
                    'price_delta' => 0,
                    'error' => "El grupo '{$group['name']}' permite máximo {$group['max_selections']} opción(es)."
                ];
            }

            // Validar que las opciones pertenecen al grupo y calcular delta
            foreach ($selected as $optId) {
                $found = false;
                foreach ($group['options'] as $opt) {
                    if ((int)$opt['id'] === (int)$optId) {
                        $totalDelta += (float)$opt['price_delta'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    return [
                        'valid' => false,
                        'price_delta' => 0,
                        'error' => "Opción no válida para el grupo '{$group['name']}'."
                    ];
                }
            }
        }

        return ['valid' => true, 'price_delta' => $totalDelta, 'error' => ''];
    }

    /**
     * Genera una clave de configuración determinística basada en el producto + opciones normalizadas.
     */
    public static function configurationKey(int $dishId, array $selectedOptions): string {
        $parts = [$dishId];
        foreach ($selectedOptions as $opt) {
            $parts[] = ($opt['group_id'] ?? 0) . ':' . ($opt['option_id'] ?? 0);
        }
        sort($parts);
        return md5(implode('|', $parts));
    }
}
