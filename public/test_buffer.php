<?php
session_start();
$_SESSION['business_id'] = 1;
require_once __DIR__ . '/../config/Database.php';

// Mock the controller loading the view
class MockController {
    public function view($view, $data = []) {
        extract($data);
        ob_start();
        require __DIR__ . '/../' . $view . '.php';
        return ob_get_clean();
    }
}

require_once __DIR__ . '/../modules/inventory/models/Product.php';
require_once __DIR__ . '/../modules/inventory/models/ProductPresentation.php';
$categories = [];
$brands = [];
require_once __DIR__ . '/../core/UnitConversionService.php';

$id = 12; // Arroz Princesa
$productModel = new Product();
$product = $productModel->find($id);
$product['dynamic_attributes'] = $productModel->getMeta($id);
$savedSaleUnitId = $product['sale_unit_id'] ?? null;
if ($savedSaleUnitId) {
    $product['stock'] = UnitConversionService::convertFromBase((float)($product['stock'] ?? 0), $savedSaleUnitId);
    $product['min_stock'] = UnitConversionService::convertFromBase((float)($product['min_stock'] ?? 0), $savedSaleUnitId);
}
$presModel = new ProductPresentation();
$presentations = $presModel->getByProduct($id);
// Empty

$c = new MockController();
$html = $c->view('modules/inventory/views/edit', [
    'product' => $product,
    'presentations' => $presentations,
    'categories' => $categories,
    'brands' => $brands
]);

// Extract the select block
preg_match('/<select name="measurement_type".*?<\/select>/s', $html, $matches);
echo "--- MEASUREMENT TYPE SELECT ---\n";
echo $matches[0] ?? "Not found";
echo "\n";

echo "--- ALERT SNIPPET ---\n";
if (strpos($html, 'SISTEMA ACTUALIZADO LOCALMENTE') !== false) {
    echo "ALERT FOUND IN HTML\n";
} else {
    echo "ALERT NOT FOUND IN HTML\n";
}
echo "\n";
?>
