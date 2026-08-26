<?php
require_once __DIR__ . '/../../../core/Controller.php';
require_once __DIR__ . '/../../../config/Database.php';
require_once __DIR__ . '/../../../core/UnitConversionService.php';
require_once __DIR__ . '/../models/Recipe.php';
require_once __DIR__ . '/../../inventory/models/Product.php';

class RestaurantController extends Controller {
    private $recipeModel;
    private $productModel;

    public function __construct() {
        $this->recipeModel = new Recipe();
        $this->productModel = new Product();
    }

    private function compressImageToBase64($tmpName, $type) {
        if (!function_exists('imagecreatefromjpeg')) {
            return 'data:' . $type . ';base64,' . base64_encode(file_get_contents($tmpName));
        }
        $maxWidth = 400; $maxHeight = 400;
        list($width, $height) = getimagesize($tmpName);
        if (!$width || !$height) return null;
        
        $ratio = min($maxWidth/$width, $maxHeight/$height);
        $newWidth = ($width <= $maxWidth && $height <= $maxHeight) ? $width : round($width * $ratio);
        $newHeight = ($width <= $maxWidth && $height <= $maxHeight) ? $height : round($height * $ratio);
        
        $src = null;
        if ($type == 'image/jpeg' || $type == 'image/jpg') $src = @imagecreatefromjpeg($tmpName);
        elseif ($type == 'image/png') $src = @imagecreatefrompng($tmpName);
        elseif ($type == 'image/webp') $src = @imagecreatefromwebp($tmpName);
        
        if (!$src) return 'data:'.$type.';base64,'.base64_encode(file_get_contents($tmpName));
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        
        if ($type == 'image/png' || $type == 'image/webp') {
            imagealphablending($dst, false); imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        ob_start();
        if ($type == 'image/png') imagepng($dst, null, 8);
        elseif ($type == 'image/webp') imagewebp($dst, null, 80);
        else imagejpeg($dst, null, 85);
        $imgData = ob_get_clean();
        imagedestroy($src); imagedestroy($dst);
        return 'data:'.$type.';base64,'.base64_encode($imgData);
    }

    /* ============================================================
       HELPERS
       ============================================================ */

    private function getIngredients() {
        $sql = "SELECT id, name, unit_cost, price, stock, measurement_type,
                       sale_unit_id, base_unit_id, purchase_unit_id, contained_unit_id, content_per_purchase
                FROM products
                WHERE (is_dish = FALSE OR is_dish IS NULL)";
        $params = [];
        if (isset($_SESSION['business_id'])) {
            $sql .= " AND tenant_id = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }
        $sql .= " ORDER BY name ASC";

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getDishes() {
        $sql = "SELECT * FROM products WHERE is_dish = TRUE";
        $params = [];
        if (isset($_SESSION['business_id'])) {
            $sql .= " AND tenant_id = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }
        $sql .= " ORDER BY name ASC";

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Lee las filas de ingredientes enviadas por el formulario (arrays paralelos) */
    private function readRecipeItemsFromPost() {
        $items = [];
        $ids = $_POST['ingredient_id'] ?? [];
        $qtys = $_POST['quantity'] ?? [];
        $units = $_POST['unit_id'] ?? [];

        foreach ($ids as $i => $ingId) {
            $qty = (float)($qtys[$i] ?? 0);
            if (empty($ingId) || $qty <= 0) continue;
            $items[] = [
                'ingredient_id' => (int)$ingId,
                'quantity'      => $qty,
                'unit_id'       => !empty($units[$i]) ? (int)$units[$i] : null,
                'notes'         => null
            ];
        }
        return $items;
    }

    /* ============================================================
       VISTAS
       ============================================================ */

    /**
     * GET restaurant - Lista de platos creados
     */
    public function index() {
        $dishes = [];
        foreach ($this->getDishes() as $dish) {
            $cost = $this->recipeModel->calculateCost($dish['id']);
            $servings = $this->recipeModel->getAvailableServings($dish['id']);
            $price = (float)($dish['price'] ?? 0);

            $dishes[] = array_merge($dish, [
                'recipe_cost' => $cost,
                'profit' => $price - $cost,
                'available_servings' => $servings,
                'ingredients_count' => count($this->recipeModel->getForDish($dish['id']))
            ]);
        }

        $this->view('modules/restaurant/views/index', [
            'dishes' => $dishes
        ]);
    }

    /**
     * GET restaurant/create_view - Formulario para crear un plato nuevo
     */
    public function create_view() {
        $this->view('modules/restaurant/views/dish_form', [
            'mode' => 'create',
            'product' => null,
            'ingredients' => $this->getIngredients(),
            'units' => UnitConversionService::getAllUnits(),
            'recipeItems' => []
        ]);
    }

    /**
     * GET restaurant/edit_dish_view/{id} - Formulario para editar plato + receta
     */
    public function edit_dish_view($id) {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM products WHERE id = :id";
        $params = ['id' => $id];
        if (isset($_SESSION['business_id'])) {
            $sql .= " AND tenant_id = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product || empty($product['is_dish'])) {
            header('Location: ' . BASE_URL . 'restaurant');
            exit;
        }

        $this->view('modules/restaurant/views/dish_form', [
            'mode' => 'edit',
            'product' => $product,
            'ingredients' => $this->getIngredients(),
            'units' => UnitConversionService::getAllUnits(),
            'recipeItems' => $this->recipeModel->getForDish($id)
        ]);
    }

    /* ============================================================
       ACCIONES
       ============================================================ */

    /**
     * POST restaurant/create - Crea el plato con su receta en un solo paso
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'restaurant');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            header('Location: ' . BASE_URL . 'restaurant/create_view?error=name');
            exit;
        }

        try {
            // Generar SKU automático
            $sku = 'PLT-' . date('YmdHis') . rand(10, 99);

            $imagePath = null;
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $fileMimeType = mime_content_type($_FILES['image']['tmp_name']);
                if (in_array($fileMimeType, $allowedMimeTypes) && filesize($_FILES['image']['tmp_name']) <= 3 * 1024 * 1024) {
                    $imagePath = $this->compressImageToBase64($_FILES['image']['tmp_name'], $_FILES['image']['type']);
                }
            }

            $data = [
                'name' => $name,
                'sku' => $sku,
                'barcode' => '',
                'image' => $imagePath,
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'currency' => $_POST['currency'] ?? 'USD',
                'price' => (float)($_POST['price'] ?? 0),
                'profit_margin' => (float)($_POST['profit_margin'] ?? 0),
                'unit_cost' => 0,
                'is_tax_exempt' => isset($_POST['is_tax_exempt']) ? 1 : 0,

                // Motor de unidades: el plato se vende por unidad
                'measurement_type' => 'unidad',
                'base_unit_id' => 3,
                'purchase_unit_id' => 3,
                'content_per_purchase' => 1.0,
                'contained_unit_id' => 3,
                'sale_unit_id' => 3,

                // El plato no tiene stock propio: vive en los ingredientes
                'stock' => 0,
                'min_stock' => 0,
                'allow_fractional_sales' => 0,

                // Restaurante
                'is_dish' => 1,
                'prep_time' => !empty($_POST['prep_time']) ? (int)$_POST['prep_time'] : null,

                // Legacy
                'unit_of_measure' => 'Migrado',
                'cost_type' => 'unit',
                'bulk_cost' => 0,
                'units_per_bulk' => 1,
                'conversion_factor' => 1.0
            ];

            $dishId = $this->productModel->createWithMeta($data, []);
            if (!$dishId) throw new Exception("No se pudo crear el plato.");

            $this->recipeModel->saveRecipe($dishId, $this->readRecipeItemsFromPost());

            header('Location: ' . BASE_URL . 'restaurant');
            exit;
        } catch (Exception $e) {
            error_log('[Restaurant] create: ' . $e->getMessage());
            header('Location: ' . BASE_URL . 'restaurant/create_view?error=1');
            exit;
        }
    }

    /**
     * POST restaurant/update/{id} - Actualiza datos del plato y su receta
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
            header('Location: ' . BASE_URL . 'restaurant');
            exit;
        }

        try {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'price' => (float)($_POST['price'] ?? 0),
                'profit_margin' => (float)($_POST['profit_margin'] ?? 0),
                'prep_time' => !empty($_POST['prep_time']) ? (int)$_POST['prep_time'] : null,
                'is_dish' => 1
            ];
            if (!empty($_POST['category_id'])) {
                $data['category_id'] = (int)$_POST['category_id'];
            }
            if (!empty($_POST['currency'])) {
                $data['currency'] = $_POST['currency'];
            }
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $fileMimeType = mime_content_type($_FILES['image']['tmp_name']);
                if (in_array($fileMimeType, $allowedMimeTypes) && filesize($_FILES['image']['tmp_name']) <= 3 * 1024 * 1024) {
                    $data['image'] = $this->compressImageToBase64($_FILES['image']['tmp_name'], $_FILES['image']['type']);
                }
            }

            $this->productModel->update($id, $data);
            $this->recipeModel->saveRecipe($id, $this->readRecipeItemsFromPost());

            header('Location: ' . BASE_URL . 'restaurant');
            exit;
        } catch (Exception $e) {
            error_log('[Restaurant] update: ' . $e->getMessage());
            header('Location: ' . BASE_URL . 'restaurant/edit_dish_view/' . $id . '?error=1');
            exit;
        }
    }

    /**
     * GET restaurant/delete/{id} - Elimina el plato (la receta se borra en cascada)
     */
    public function delete($id) {
        if ($id) {
            try {
                $this->productModel->delete($id);
            } catch (\Exception $e) {
                http_response_code(400);
                echo "dependency_error";
                exit;
            }
        }
        http_response_code(200);
        echo "OK";
        exit;
    }

    /* ============================================================
       API JSON (para integraciones futuras / POS)
       ============================================================ */

    public function ingredients() {
        $this->jsonResponse(['success' => true, 'data' => $this->getIngredients()]);
    }

    public function getRecipe($dishId) {
        try {
            $items = $this->recipeModel->getForDish($dishId);
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'items' => $items,
                    'cost' => $this->recipeModel->calculateCost($dishId),
                    'available_servings' => $this->recipeModel->getAvailableServings($dishId)
                ]
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function saveRecipe($dishId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $items = $input['items'] ?? [];

            if (!is_array($items)) {
                throw new Exception("Formato de receta inválido.");
            }

            $this->recipeModel->saveRecipe($dishId, $items);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Receta guardada',
                'cost' => $this->recipeModel->calculateCost($dishId),
                'available_servings' => $this->recipeModel->getAvailableServings($dishId)
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function cost($dishId) {
        try {
            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'cost' => $this->recipeModel->calculateCost($dishId),
                    'available_servings' => $this->recipeModel->getAvailableServings($dishId)
                ]
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
