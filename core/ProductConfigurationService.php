<?php
/**
 * ProductConfigurationService - Servicio común para manejar productos configurables.
 * Usado por POS, Storefront y QR Menu.
 * 
 * Responsabilidades:
 * - loadConfiguration(): carga grupos y opciones de un plato
 * - validateConfiguration(): valida selección del cliente
 * - calculateConfigurationPrice(): calcula precio final desde BD
 * - calculateConfigurationAvailability(): disponibilidad máxima
 */
require_once __DIR__ . '/../modules/restaurant/models/RestaurantOption.php';

class ProductConfigurationService {

    private $db;
    private $tenantId;

    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
        $this->tenantId = $_SESSION['business_id'] ?? null;
    }

    /**
     * Carga la configuración completa de un plato (grupos + opciones con precios).
     */
    public function loadConfiguration(int $dishId): array {
        $optionModel = new RestaurantOption();
        return $optionModel->getGroupsForDish($dishId);
    }

    /**
     * Valida una configuración enviada por el cliente.
     * Retorna: ['valid' => bool, 'price_delta' => float, 'error' => string]
     * El backend SIEMPRE calcula el precio, nunca confía en el frontend.
     */
    public function validateConfiguration(int $dishId, array $selectedOptions): array {
        $optionModel = new RestaurantOption();
        return $optionModel->validateConfiguration($dishId, $selectedOptions);
    }

    /**
     * Calcula el precio final de un plato configurado.
     * Precio base del plato + sum of price_deltas de las opciones seleccionadas.
     * El backend es la fuente de verdad.
     */
    public function calculateConfigurationPrice(int $dishId, array $selectedOptions): float {
        // Precio base del plato
        $stmt = $this->db->prepare("SELECT price FROM products WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$dishId, $this->tenantId]);
        $basePrice = (float)$stmt->fetchColumn();

        // Delta de opciones
        $validation = $this->validateConfiguration($dishId, $selectedOptions);
        if (!$validation['valid']) {
            return $basePrice; // Fallback al precio base si la validación falla
        }

        return $basePrice + $validation['price_delta'];
    }

    /**
     * Calcula la disponibilidad máxima de un plato configurado.
     * Es el mínimo de: disponibilidad del plato base Y disponibilidad de cada opción.
     */
    public function calculateConfigurationAvailability(int $dishId, array $selectedOptions): array {
        require_once __DIR__ . '/InventoryConsumptionService.php';
        $inventoryService = new InventoryConsumptionService();

        // Disponibilidad del plato base (por su receta)
        $baseAvail = $inventoryService->checkAvailability($dishId, 1);
        $maxFromDish = $baseAvail['available'] ? $baseAvail['max_qty'] : 0;

        // Disponibilidad de cada opción seleccionada
        // IMPORTANTE: option_id es restaurant_options.id, NO product_id
        // Debemos resolver option_id -> product_id antes de verificar disponibilidad
        $maxFromOptions = PHP_FLOAT_MAX;
        $optionModel = new \RestaurantOption();
        foreach ($selectedOptions as $opt) {
            $optionId = $opt['option_id'] ?? 0;
            if ($optionId <= 0) continue;

            // Resolver el product_id desde restaurant_options
            $stmtOpt = $this->db->prepare("SELECT product_id FROM restaurant_options WHERE id = ? AND tenant_id = ?");
            $stmtOpt->execute([$optionId, $this->tenantId]);
            $optionProductId = (int)$stmtOpt->fetchColumn();

            if ($optionProductId <= 0) continue;

            $optAvail = $inventoryService->checkAvailability($optionProductId, 1);
            $maxOpt = $optAvail['available'] ? $optAvail['max_qty'] : 0;
            if ($maxOpt < $maxFromOptions) {
                $maxFromOptions = $maxOpt;
            }
        }

        $maxQty = min($maxFromDish, $maxFromOptions);
        return [
            'available' => $maxQty > 0,
            'max_qty' => $maxQty,
            'reason' => $maxQty <= 0 ? 'No hay suficiente stock para esta configuración.' : ''
        ];
    }

    /**
     * Genera una clave de configuración determinística.
     * Usada para identificar líneas únicas en el carrito.
     */
    public static function configurationKey(int $dishId, array $selectedOptions): string {
        return RestaurantOption::configurationKey($dishId, $selectedOptions);
    }

    /**
     * Prepara los datos de un item configurado para insertar en sale_items / store_order_items.
     * Retorna un array con la información normalizada.
     */
    public function prepareItemData(int $dishId, float $quantity, array $selectedOptions, float $unitPrice): array {
        $configKey = self::configurationKey($dishId, $selectedOptions);
        $optionModel = new RestaurantOption();
        $groups = $optionModel->getGroupsForDish($dishId);

        $optionSnapshots = [];
        foreach ($selectedOptions as $opt) {
            $groupId = $opt['group_id'] ?? 0;
            $optionId = $opt['option_id'] ?? 0;
            $groupName = '';
            $optionName = '';
            $priceDelta = 0;

            foreach ($groups as $group) {
                if ((int)$group['id'] === $groupId) {
                    $groupName = $group['name'];
                    foreach ($group['options'] as $option) {
                        if ((int)$option['id'] === $optionId) {
                            $optionName = $option['product_name'];
                            $priceDelta = (float)$option['price_delta'];
                            break;
                        }
                    }
                    break;
                }
            }

            $optionSnapshots[] = [
                'group_id' => $groupId,
                'option_product_id' => $optionId,
                'group_name_snapshot' => $groupName,
                'option_name_snapshot' => $optionName,
                'price_delta' => $priceDelta,
            ];
        }

        return [
            'configuration_key' => $configKey,
            'unit_price' => $unitPrice,
            'options' => $optionSnapshots,
        ];
    }
}
