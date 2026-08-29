<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Brand.php';
require_once __DIR__ . '/../models/ProductPresentation.php';
require_once __DIR__ . '/../../../core/UnitConversionService.php';

class InventoryController extends Controller {
    private $productModel;
    private $categoryModel;
    private $brandModel;

    public function __construct() {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->brandModel = new Brand();
    }

    private function compressImageToBase64($tmpName, $type) {
        // Fallback robusto si la extensión GD de PHP no está habilitada en el servidor (ej: en algunos XAMPP/Vercel)
        if (!function_exists('imagecreatefromjpeg')) {
            return 'data:' . $type . ';base64,' . base64_encode(file_get_contents($tmpName));
        }

        $maxWidth = 400;
        $maxHeight = 400;
        
        list($width, $height) = getimagesize($tmpName);
        if (!$width || !$height) return null;
        
        $ratio = min($maxWidth/$width, $maxHeight/$height);
        $newWidth = ($width <= $maxWidth && $height <= $maxHeight) ? $width : round($width * $ratio);
        $newHeight = ($width <= $maxWidth && $height <= $maxHeight) ? $height : round($height * $ratio);
        
        $src = null;
        if ($type == 'image/jpeg' || $type == 'image/jpg') $src = @imagecreatefromjpeg($tmpName);
        elseif ($type == 'image/png') $src = @imagecreatefrompng($tmpName);
        elseif ($type == 'image/webp') $src = @imagecreatefromwebp($tmpName);
        
        // Return raw base64 if GD fails or format unsupported
        if (!$src) return 'data:'.$type.';base64,'.base64_encode(file_get_contents($tmpName));
        
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        
        if ($type == 'image/png' || $type == 'image/webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        ob_start();
        if ($type == 'image/png') {
            imagepng($dst, null, 8);
        } elseif ($type == 'image/webp') {
            imagewebp($dst, null, 80);
        } else {
            imagejpeg($dst, null, 85);
        }
        $data = ob_get_clean();
        
        imagedestroy($src);
        imagedestroy($dst);
        
        $outType = $type ?: 'image/jpeg';
        return 'data:' . $outType . ';base64,' . base64_encode($data);
    }

    public function index() {
        $categories = $this->categoryModel->all();
        $brands = $this->brandModel->all();
        // Sort them for the select boxes
        usort($brands, function($a, $b) { return strcmp($a['name'], $b['name']); });
        usort($categories, function($a, $b) { return strcmp($a['name'], $b['name']); });
        
        require_once __DIR__ . '/../../../core/UnitConversionService.php';
        $units = UnitConversionService::getAllUnits();

        $this->view('modules/inventory/views/index', [
            'categories' => $categories, 
            'brands' => $brands,
            'units' => $units
        ]);
    }

    public function list() {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        $products = $this->productModel->allWithCategoriesAndBrands();
        require __DIR__ . '/../views/table_body.php';
    }

    public function image() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $this->productModel->db->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $base64 = $stmt->fetchColumn();
            if ($base64 && strpos($base64, 'data:image') === 0) {
                list($type, $data) = explode(';', $base64);
                list(, $data)      = explode(',', $data);
                $imgData = base64_decode($data);
                $mime = str_replace('data:', '', $type);
                header("Content-Type: $mime");
                header('Cache-Control: public, max-age=86400'); // Cache for 24 hours on CDN
                echo $imgData;
                exit;
            }
        }
        
        // Fallback transparent 1x1 image
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        exit;
    }

    public function print() {
        $products = $this->productModel->allWithCategoriesAndBrands();
        $this->view('modules/inventory/views/print', ['products' => $products]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Handle dynamically added Category
            if (isset($_POST['category_id']) && $_POST['category_id'] === 'new' && !empty($_POST['new_category'])) {
                $newCatId = $this->categoryModel->create(['name' => $_POST['new_category']]);
                $_POST['category_id'] = $newCatId;
            }
            
            // Handle dynamically added Brand / Supplier
            if (isset($_POST['brand_id']) && $_POST['brand_id'] === 'new' && !empty($_POST['new_brand'])) {
                // For this project we renamed brand to supplier conceptually in the UI for the MVP
                // Assuming supplier is stored in brand model or supplier model. 
                // Let's create it in brands table since it was used as bodegas/brands previously
                $newBrandId = $this->brandModel->create(['name' => $_POST['new_brand']]);
                $_POST['brand_id'] = $newBrandId;
            }

            // Auto-SKU & Barcode generation
            $sku = 'PRD-' . date('YmdHis') . rand(10,99);
            $barcode = ltrim($_POST['barcode'] ?? '');
            if (empty($barcode)) {
                $barcode = 'B' . date('Ymd') . rand(1000, 9999);
            }

            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    http_response_code(400); header('X-Toast-Type: error'); header('X-Toast-Message: Nombre requerido'); exit;
                }
                exit('Nombre de producto requerido');
            }

            // Image Upload to Base64 (Vercel Compatibility)
            $imagePath = null;
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $fileMimeType = mime_content_type($_FILES['image']['tmp_name']);
                
                if (!in_array($fileMimeType, $allowedMimeTypes)) {
                    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                        http_response_code(400); header('X-Toast-Type: error'); header('X-Toast-Message: Tipo de imagen no permitido (solo JPG/PNG/WEBP)'); exit;
                    }
                    exit('Formato no permitido');
                }
                
                // Límite de 3MB
                if(filesize($_FILES['image']['tmp_name']) > 3 * 1024 * 1024) {
                    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                        http_response_code(400); header('X-Toast-Type: error'); header('X-Toast-Message: Imagen muy pesada (Max 3MB)'); exit;
                    }
                    exit('Imagen muy pesada');
                }
                
                // Comprimiendo a Base64 para guardarlo directamente en base de datos
                $imagePath = $this->compressImageToBase64($_FILES['image']['tmp_name'], $_FILES['image']['type']);
            }

            // Unit of measure engine
            $mType = $_POST['measurement_type'] ?? 'unidad';
            $saleUnitId = !empty($_POST['sale_unit_id']) ? (int)$_POST['sale_unit_id'] : 3;
            $purchaseUnitId = !empty($_POST['purchase_unit_id']) ? (int)$_POST['purchase_unit_id'] : 3;
            $baseUnitId = !empty($_POST['base_unit_id']) ? (int)$_POST['base_unit_id'] : 3;
            $contentPerPurchase = !empty($_POST['content_per_purchase']) ? (float)$_POST['content_per_purchase'] : 1.0;
            $containedUnitId = !empty($_POST['contained_unit_id']) ? (int)$_POST['contained_unit_id'] : 3;

            require_once __DIR__ . '/../../../core/UnitConversionService.php';
            $saleUnit = \UnitConversionService::getUnit($saleUnitId);
            $saleUnitName = $saleUnit ? $saleUnit['name'] : 'Unidad';
            
            // Backend decides cost per base unit
            $totalCost = (float)($_POST['total_cost'] ?? 0);
            require_once __DIR__ . '/../../../core/CostCalculationService.php';
            $unitCost = 0;
            if ($totalCost > 0 && $contentPerPurchase > 0) {
                // The purchase is $totalCost for $contentPerPurchase of the Sale Unit.
                $unitCost = $totalCost / $contentPerPurchase;
            } elseif (isset($_POST['unit_cost']) && $_POST['unit_cost'] !== '') {
                $unitCost = (float)$_POST['unit_cost'];
            }

            // Convertir stock introducido a unidad base
            $stockInSaleUnit = (float)($_POST['stock'] ?? 0);
            $stockInBase = UnitConversionService::convertToBase($stockInSaleUnit, $saleUnitId);
            $minStockInBase = UnitConversionService::convertToBase((float)($_POST['min_stock'] ?? 5), $saleUnitId);

            $data = [
                'name' => $name,
                'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                'brand_id' => !empty($_POST['brand_id']) ? $_POST['brand_id'] : null,
                'sku' => $sku,
                'barcode' => $barcode,
                
                // Costs and Prices
                'currency' => $_POST['currency'] ?? 'USD',
                'profit_margin' => $_POST['profit_margin'] ?? 0,
                'price' => (float)($_POST['price'] ?? 0),
                'unit_cost' => $unitCost,
                'is_tax_exempt' => isset($_POST['is_tax_exempt']) ? 1 : 0,
                
                // Unit Engine
                'purchase_unit_id' => $purchaseUnitId,
                'sale_unit_id' => $saleUnitId,
                
                // Inventory
                'stock' => $stockInBase,
                'min_stock' => $minStockInBase,
                'allow_fractional_sales' => isset($_POST['allow_fractional_sales']) ? 1 : 0,

                // Legacy fields (kept for old views fallback)
                'unit_of_measure' => $saleUnitName,
                'cost_type' => 'unit',
                'bulk_cost' => 0,
                'units_per_bulk' => 1,
                'conversion_factor' => $contentPerPurchase,
                
                // Unit Engine (Real Columns)
                'measurement_type' => $mType,
                'base_unit_id' => $baseUnitId,
                'contained_unit_id' => $containedUnitId,
                'content_per_purchase' => $contentPerPurchase
            ];
            
            if ($imagePath) {
                $data['image'] = $imagePath;
            }
            
            $metaAttributes = [
                'measurement_type' => $mType,
                'base_unit_id' => $baseUnitId,
                'contained_unit_id' => $containedUnitId,
                'content_per_purchase' => $contentPerPurchase
            ];

            try {
                $productId = $this->productModel->createWithMeta($data, $metaAttributes);
                if ($productId) {
                    // Sync Presentations
                    $presentationModel = new ProductPresentation();
                    $presentations = [];
                    if (!empty($_POST['presentation_names'])) {
                        for ($i = 0; $i < count($_POST['presentation_names']); $i++) {
                            if (!empty(trim($_POST['presentation_names'][$i])) && (float)$_POST['presentation_quantities'][$i] > 0) {
                                $presentations[] = [
                                    'name' => $_POST['presentation_names'][$i],
                                    'quantity' => $_POST['presentation_quantities'][$i],
                                    'unit_id' => $_POST['presentation_units'][$i]
                                ];
                            }
                        }
                    } 
                    if (empty($presentations)) {
                        $pr_name = empty($_POST['unit_of_measure']) ? "Presentación Default" : $_POST['unit_of_measure'];
                        $pr_qty  = empty($_POST['content_per_purchase']) ? 1 : $_POST['content_per_purchase'];
                        $pr_unit = empty($_POST['contained_unit_id']) ? null : $_POST['contained_unit_id'];
                        $presentations[] = ['name' => $pr_name, 'quantity' => $pr_qty, 'unit_id' => $pr_unit];
                    }
                    $presentationModel->syncForProduct($productId, $presentations);

                    // If HTMX request, just return success
                    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                        http_response_code(200);
                        echo "OK";
                        exit;
                    }
                    header('Location: ' . BASE_URL . 'inventory');
                    exit;
                } else {
                    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                        http_response_code(400);
                        echo "Error al guardar";
                        exit;
                    }
                    $error = "Error al guardar el producto.";
                }
            } catch (\Exception $e) {
                error_log("Inventory Create Error: " . $e->getMessage());
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    http_response_code(400);
                    // Safe encoding for header
                    $msg = str_replace(["\r", "\n"], ' ', $e->getMessage());
                    header('X-Toast-Type: error');
                    header('X-Toast-Message: ' . substr($msg, 0, 200));
                    echo "Error de BD: " . $msg;
                    exit;
                }
                exit('Error Critico de Base de Datos: ' . $e->getMessage());
            }
        }
    }
    public function create_view() {
        $categories = $this->categoryModel->all();
        $brands = $this->brandModel->all();
        usort($brands, function($a, $b) { return strcmp($a['name'], $b['name']); });
        usort($categories, function($a, $b) { return strcmp($a['name'], $b['name']); });
        
        require_once __DIR__ . '/../../../core/UnitConversionService.php';
        $units = UnitConversionService::getAllUnits();

        $this->view('modules/inventory/views/create', [
            'categories' => $categories, 
            'brands' => $brands,
            'units' => $units
        ]);
    }

    public function edit_view($id) {
        $product = $this->productModel->find($id);
        if (!$product) {
            header('Location: ' . BASE_URL . 'inventory');
            exit;
        }
        
        $product['dynamic_attributes'] = $this->productModel->getMeta($id);
        
        require_once __DIR__ . '/../../../core/UnitConversionService.php';
        if (!empty($product['sale_unit_id'])) {
            $product['stock'] = \UnitConversionService::convertFromBase((float)($product['stock'] ?? 0), $product['sale_unit_id']);
            $product['min_stock'] = \UnitConversionService::convertFromBase((float)($product['min_stock'] ?? 0), $product['sale_unit_id']);
        }
        
        $presModel = new ProductPresentation();
        $presentations = $presModel->getByProduct($id);
        
        $categories = $this->categoryModel->all();
        $brands = $this->brandModel->all();
        usort($brands, function($a, $b) { return strcmp($a['name'], $b['name']); });
        usort($categories, function($a, $b) { return strcmp($a['name'], $b['name']); });
        
        require_once __DIR__ . '/../../../core/UnitConversionService.php';
        $units = UnitConversionService::getAllUnits();

        $this->view('modules/inventory/views/edit', [
            'product' => $product,
            'presentations' => $presentations,
            'categories' => $categories, 
            'brands' => $brands,
            'units' => $units
        ]);
    }

    public function edit($id) {
        // Return JSON product data for modal pre-fill
        $product = $this->productModel->find($id);
        if ($product) {
            require_once __DIR__ . '/../models/ProductPresentation.php';
            $presModel = new ProductPresentation();
            $product['presentations'] = $presModel->getByProduct($id);
            $this->jsonResponse($product);
        } else {
            $this->jsonResponse(['error' => 'Producto no encontrado'], 404);
        }
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
            // Handle dynamic category
            if (isset($_POST['category_id']) && $_POST['category_id'] === 'new' && !empty($_POST['new_category'])) {
                $newCatId = $this->categoryModel->create(['name' => $_POST['new_category']]);
                $_POST['category_id'] = $newCatId;
            }

            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    http_response_code(400); header('X-Toast-Type: error'); header('X-Toast-Message: Nombre requerido'); exit;
                }
                exit('Nombre de producto requerido');
            }

            // Unit of measure engine
            $mType = $_POST['measurement_type'] ?? 'unidad';
            $saleUnitId = !empty($_POST['sale_unit_id']) ? (int)$_POST['sale_unit_id'] : 3;
            $purchaseUnitId = !empty($_POST['purchase_unit_id']) ? (int)$_POST['purchase_unit_id'] : 3;
            $baseUnitId = !empty($_POST['base_unit_id']) ? (int)$_POST['base_unit_id'] : 3;
            $contentPerPurchase = !empty($_POST['content_per_purchase']) ? (float)$_POST['content_per_purchase'] : 1.0;
            $containedUnitId = !empty($_POST['contained_unit_id']) ? (int)$_POST['contained_unit_id'] : 3;

            require_once __DIR__ . '/../../../core/UnitConversionService.php';
            $saleUnit = \UnitConversionService::getUnit($saleUnitId);
            $saleUnitName = $saleUnit ? $saleUnit['name'] : 'Unidad';

            // Backend decides cost per base unit
            $totalCost = (float)($_POST['total_cost'] ?? 0);
            require_once __DIR__ . '/../../../core/CostCalculationService.php';
            $unitCost = $this->productModel->find($id)['unit_cost'] ?? 0; // Fallback to current cost
            if (isset($_POST['total_cost']) && $totalCost >= 0 && $contentPerPurchase > 0) {
                // The purchase is $totalCost for $contentPerPurchase of the Sale Unit
                $unitCost = $totalCost / $contentPerPurchase;
            } elseif (isset($_POST['unit_cost']) && $_POST['unit_cost'] !== '') {
                $unitCost = (float)$_POST['unit_cost'];
            }
            
            // OJO: Al actualizar, si el frontend envía el stock modificado, lo convertimos a base.
            // Si el frontend no modifica el stock manual, es mejor ignorarlo o recalcularlo basado en el sale unit.
            $stockInSaleUnit = (float)($_POST['stock'] ?? 0);
            $stockInBase = UnitConversionService::convertToBase($stockInSaleUnit, $saleUnitId);
            $minStockInBase = UnitConversionService::convertToBase((float)($_POST['min_stock'] ?? 5), $saleUnitId);

            $data = [
                'name' => $name,
                'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                'brand_id' => !empty($_POST['brand_id']) ? $_POST['brand_id'] : null,
                'barcode' => $_POST['barcode'] ?? '',
                
                // Costs and Prices
                'currency' => $_POST['currency'] ?? 'USD',
                'profit_margin' => $_POST['profit_margin'] ?? 0,
                'price' => (float)($_POST['price'] ?? 0),
                'unit_cost' => $unitCost,
                'is_tax_exempt' => isset($_POST['is_tax_exempt']) ? 1 : 0,
                
                // Unit Engine
                'purchase_unit_id' => $purchaseUnitId,
                'sale_unit_id' => $saleUnitId,
                'conversion_factor' => $contentPerPurchase,
                
                // Inventory
                'stock' => $stockInBase,
                'min_stock' => $minStockInBase,
                'allow_fractional_sales' => isset($_POST['allow_fractional_sales']) ? 1 : 0,

                // Legacy
                'unit_of_measure' => $saleUnitName,
                
                // Unit Engine (Real Columns)
                'measurement_type' => $mType,
                'base_unit_id' => $baseUnitId,
                'contained_unit_id' => $containedUnitId,
                'content_per_purchase' => $contentPerPurchase
            ];

            // Image upload (optional) - Vercel Compatibility Base64
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                $fileMimeType = mime_content_type($_FILES['image']['tmp_name']);
                
                if (!in_array($fileMimeType, $allowedMimeTypes)) {
                    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                        http_response_code(400); header('X-Toast-Type: error'); header('X-Toast-Message: Tipo no permitido'); exit;
                    }
                    exit('Formato no permitido');
                }
                
                if(filesize($_FILES['image']['tmp_name']) > 3 * 1024 * 1024) {
                    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                        http_response_code(400); header('X-Toast-Type: error'); header('X-Toast-Message: Max 3MB superado'); exit;
                    }
                    exit('Imagen muy pesada');
                }

                $base64 = $this->compressImageToBase64($_FILES['image']['tmp_name'], $_FILES['image']['type']);
                if ($base64) {
                    $data['image'] = $base64;
                }
            }

            // Dynamic Attributes
            $metaAttributes = [
                'measurement_type' => $mType,
                'base_unit_id' => $baseUnitId,
                'contained_unit_id' => $containedUnitId,
                'content_per_purchase' => $contentPerPurchase
            ];
            if (!empty($_POST['meta_key']) && is_array($_POST['meta_key'])) {
                $keys = $_POST['meta_key'];
                $values = $_POST['meta_value'] ?? [];
                foreach ($keys as $index => $key) {
                    if (!empty(trim($key))) {
                        $metaAttributes[trim($key)] = trim($values[$index] ?? '');
                    }
                }
            }

            try {
                if ($this->productModel->updateWithMeta($id, $data, $metaAttributes)) {
                    // Sync Presentations
                    $presentationModel = new ProductPresentation();
                    $presentations = [];
                    if (!empty($_POST['presentation_names'])) {
                        for ($i = 0; $i < count($_POST['presentation_names']); $i++) {
                            if (!empty(trim($_POST['presentation_names'][$i])) && (float)$_POST['presentation_quantities'][$i] > 0) {
                                $presentations[] = [
                                    'name' => $_POST['presentation_names'][$i],
                                    'quantity' => $_POST['presentation_quantities'][$i],
                                    'unit_id' => $_POST['presentation_units'][$i]
                                ];
                            }
                        }
                    } 
                    if (empty($presentations)) {
                        $pr_name = empty($_POST['unit_of_measure']) ? "Presentación Default" : $_POST['unit_of_measure'];
                        $pr_qty  = empty($_POST['content_per_purchase']) ? 1 : $_POST['content_per_purchase'];
                        $pr_unit = empty($_POST['contained_unit_id']) ? null : $_POST['contained_unit_id'];
                        $presentations[] = ['name' => $pr_name, 'quantity' => $pr_qty, 'unit_id' => $pr_unit];
                    }
                    $presentationModel->syncForProduct($id, $presentations);

                    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                        http_response_code(200);
                        echo "OK";
                        exit;
                    }
                    header('Location: ' . BASE_URL . 'inventory');
                    exit;
                } else {
                    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                        http_response_code(400);
                        header('X-Toast-Type: error');
                        header('X-Toast-Message: Error al actualizar (¿Código de Barras Duplicado?)');
                        exit;
                    }
                }
            } catch (\Exception $e) {
                error_log("Inventory Update Error: " . $e->getMessage());
                if (isset($_SERVER['HTTP_HX_REQUEST'])) {
                    http_response_code(400);
                    $msg = str_replace(["\r", "\n"], ' ', $e->getMessage());
                    header('X-Toast-Type: error');
                    header('X-Toast-Message: ' . substr($msg, 0, 200));
                    echo "Error de BD: " . $msg;
                    exit;
                }
            }
        }
    }

    public function delete($id) {
        if ($id) {
            try {
                $this->productModel->delete($id);
            } catch (\Exception $e) {
                // Return 400 Bad Request to tell JS it failed due to dependencies
                http_response_code(400);
                echo "dependency_error";
                exit;
            }
        }
        
        http_response_code(200);
        echo "OK";
        exit;
    }

    public function bulk_import() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!empty($input) && is_array($input)) {
                $successCount = 0;
                $errorCount = 0;
                
                foreach ($input as $row) {
                    $catName = trim($row['Categoria'] ?? 'General');
                    $catId = $this->getOrCreateCategory($catName);
                    
                    $sku = !empty(trim($row['SKU'] ?? '')) ? trim($row['SKU']) : 'PRD-' . date('YmdHis') . rand(10,9999);
                    $barcode = !empty(trim($row['CodigoBarras'] ?? '')) ? trim($row['CodigoBarras']) : ('B' . date('Ymd') . rand(1000, 9999));
                    
                    $price = floatval($row['Precio'] ?? 0);
                    $cost = floatval($row['Costo'] ?? 0);
                    $stock = intval($row['Stock'] ?? 0);
                    
                    $data = [
                        'name' => trim($row['Producto'] ?? 'Producto Desconocido'),
                        'category_id' => $catId,
                        'brand_id' => null,
                        'sku' => $sku,
                        'barcode' => $barcode,
                        'cost_type' => 'unit',
                        'unit_cost' => $cost,
                        'bulk_cost' => $cost,
                        'units_per_bulk' => 1,
                        'currency' => 'USD',
                        'profit_margin' => ($cost > 0) ? ((($price / $cost) - 1) * 100) : 0,
                        'price' => $price,
                        'stock' => $stock,
                        'min_stock' => 5,
                        'unit_of_measure' => 'Unidades',
                        'is_tax_exempt' => 0
                    ];
                    
                    if ($this->productModel->createWithMeta($data, [])) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'imported' => $successCount, 'errors' => $errorCount]);
                exit;
            }
            
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Payload inválido']);
            exit;
        }
    }
    
    private function getOrCreateCategory($name) {
        if(empty($name)) $name = 'General';
        $categories = $this->categoryModel->all();
        foreach ($categories as $c) {
            if (strcasecmp((string)$c['name'], $name) === 0) {
                return $c['id'];
            }
        }
        return $this->categoryModel->create(['name' => $name]);
    }

    public function get($id) {
        $product = $this->productModel->find($id);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            exit;
        }

        $category = $this->categoryModel->find($product['category_id']);
        $product['category_name'] = $category ? $category['name'] : 'Sin Categoría';
        
        if ($product['brand_id']) {
            $brand = $this->brandModel->find($product['brand_id']);
            $product['brand_name'] = $brand ? $brand['name'] : 'Genérico';
        }

        // Meta and units
        $product['dynamic_attributes'] = $this->productModel->getMeta($id);
        
        $presModel = new ProductPresentation();
        $product['presentations'] = $presModel->getByProduct($id);

        header('Content-Type: application/json');
        echo json_encode($product);
        exit;
    }
}
