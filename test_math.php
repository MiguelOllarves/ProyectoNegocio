<?php
require_once __DIR__ . '/core/UnitConversionService.php';
require_once __DIR__ . '/core/CostCalculationService.php';

require_once __DIR__ . '/config/Database.php';

echo "=========== SETUP ===========\n";
$_SESSION['business_id'] = 1;

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, name, base_type FROM units_of_measure WHERE name IN ('Gramo', 'Kilogramo')");
$units = $stmt->fetchAll(PDO::FETCH_ASSOC);

$gramoId = null;
$kiloId = null;
foreach($units as $u) {
    if ($u['name'] === 'Gramo') $gramoId = $u['id'];
    if ($u['name'] === 'Kilogramo') $kiloId = $u['id'];
}
echo "Gramo ID: $gramoId | Kilo ID: $kiloId \n";

echo "=========== PRUEBAS DE MOTOR MATEMATICO ===========\n";

// 1. Convertir de kg a gramos
try {
    $g = UnitConversionService::convertToBase(40, $kiloId);
    echo "[TEST 1] 40 kg a base: $g (Esperado: 40000)\n";
} catch (Exception $e) {
    echo "ERROR en Test 1: " . $e->getMessage() . "\n";
}

// 2. Costo por unidad base ($200 por 40kg)
// $totalCost = 200, $purchaseQty = 40 (kg), targetUnit = 4 (kg) -> Costo por gramo
try {
    $costPerGram = CostCalculationService::calculateCostPerBaseUnit(200, 40, $kiloId);
    echo "[TEST 2] Costo por gramo de (40kg por 200usd): $costPerGram (Esperado: 0.005)\n";
} catch (Exception $e) {
    echo "ERROR en Test 2: " . $e->getMessage() . "\n";
}

// 3. Costo de 100g de queso. (La receta usa 100g)
try {
    $costoEmpanada = CostCalculationService::calculateIngredientCost(100, $gramoId, 0.005);
    echo "[TEST 3] Costo 100g de Queso a 0.005c/g: $costoEmpanada (Esperado: 0.5 o 0.50)\n";
} catch (Exception $e) {
    echo "ERROR en Test 3: " . $e->getMessage() . "\n";
}

// 4. Test missing costs
echo "\n==== CASOS CONTRA COSTOS FALTANTES ====\n";
$missing1 = CostCalculationService::calculateIngredientCost(100, $gramoId, 0); // costo 0 o nulo
echo "[TEST 4] Costo ingrediente sin costo cargado: $missing1 (Esperado: MISSING_COST)\n";

$missing2 = CostCalculationService::calculateIngredientCost(100, $gramoId, null);
echo "[TEST 5] Costo ingrediente nulo: $missing2 (Esperado: MISSING_COST)\n";

echo "=========== FIN DE PRUEBAS ===========\n";
