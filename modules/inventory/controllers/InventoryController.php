<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Brand.php';

class InventoryController extends Controller {
    private $productModel;
    private $categoryModel;
    private $brandModel;

    public function __construct() {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->brandModel = new Brand();
    }

    public function index() {
        $categories = $this->categoryModel->all();
        $brands = $this->brandModel->all();
        // Sort them for the select boxes
        usort($brands, function($a, $b) { return strcmp($a['name'], $b['name']); });
        usort($categories, function($a, $b) { return strcmp($a['name'], $b['name']); });

        $this->view('modules/inventory/views/index', [
            'categories' => $categories, 
            'brands' => $brands
        ]);
    }

    public function list() {
        $products = $this->productModel->allWithCategoriesAndBrands();
        require __DIR__ . '/../views/table_body.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Handle dynamically added Category
            if (isset($_POST['category_id']) && $_POST['category_id'] === 'new' && !empty($_POST['new_category'])) {
                $newCatId = $this->categoryModel->create(['name' => $_POST['new_category']]);
                $_POST['category_id'] = $newCatId;
            }
            
            // Handle dynamically added Brand / Supplier
            if (isset($_POST['supplier_id']) && $_POST['supplier_id'] === 'new' && !empty($_POST['new_supplier'])) {
                // For this ERP we renamed brand to supplier conceptually in the UI for the MVP
                // Assuming supplier is stored in brand model or supplier model. 
                // Let's create it in brands table since it was used as bodegas/brands previously
                $newBrandId = $this->brandModel->create(['name' => $_POST['new_supplier']]);
                $_POST['supplier_id'] = $newBrandId;
            }

            // Auto-SKU & Barcode generation
            $sku = 'PRD-' . date('YmdHis') . rand(10,99);
            $barcode = ltrim($_POST['barcode'] ?? '');
            if (empty($barcode)) {
                $barcode = 'B' . date('Ymd') . rand(1000, 9999);
            }

            // Image Upload
            $imagePath = null;
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../public/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $filename = time() . '_' . basename($_FILES['image']['name']);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'uploads/' . $filename;
                }
            }

            // Unit of measure
            $unit = $_POST['unit_of_measure'] ?? 'Unidades';
            if ($unit === 'new' && !empty($_POST['new_unit'])) {
                $unit = $_POST['new_unit'];
            }

            $costType = $_POST['cost_type'] ?? 'unit';

            $data = [
                'name' => $_POST['name'] ?? '',
                'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
                'supplier_id' => !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null,
                'sku' => $sku,
                'barcode' => $barcode,
                
                // Costs and Prices
                'cost_type' => $costType,
                'unit_cost' => $_POST['unit_cost'] ?? 0,
                'bulk_cost' => $_POST['bulk_cost'] ?? 0,
                'units_per_bulk' => $_POST['units_per_bulk'] ?? 1,
                'currency' => $_POST['currency'] ?? 'USD',
                'profit_margin' => $_POST['profit_margin'] ?? 0,
                'price' => $_POST['price'] ?? 0,
                
                // Inventory
                'stock' => $_POST['stock'] ?? 0,
                'min_stock' => $_POST['min_stock'] ?? 5,
                'unit_of_measure' => $unit,
                'is_tax_exempt' => isset($_POST['is_tax_exempt']) ? 1 : 0
            ];
            
            if ($imagePath) {
                $data['image'] = $imagePath;
            }

            if ($this->productModel->createWithMeta($data, [])) {
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
        }
    }

    public function edit($id) {
        // Edit page is not strictly required by this modal task, keep it simple redirect for now
        header('Location: ' . BASE_URL . 'inventory');
        exit;
    }

    public function delete($id) {
        if ($id) {
            $this->productModel->delete($id);
        }
        header('Location: ' . BASE_URL . 'inventory');
        exit;
    }
}
