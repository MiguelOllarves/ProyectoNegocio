<?php
/**
 * Test: ProductConfigurationService
 * Verifica que el servicio de configuración de productos funciona correctamente.
 */

// Simular entorno
$_SESSION['business_id'] = 1;

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/ProductConfigurationService.php';

echo "=== Test: ProductConfigurationService ===\n\n";

// Test 1: Constructor
try {
    $service = new ProductConfigurationService();
    echo "[PASS] Constructor funciona\n";
} catch (Exception $e) {
    echo "[FAIL] Constructor: " . $e->getMessage() . "\n";
}

// Test 2: configurationKey genera claves determinísticas
$key1 = ProductConfigurationService::configurationKey(100, [
    ['group_id' => 1, 'option_id' => 200],
    ['group_id' => 2, 'option_id' => 300]
]);
$key2 = ProductConfigurationService::configurationKey(100, [
    ['group_id' => 2, 'option_id' => 300],
    ['group_id' => 1, 'option_id' => 200]
]);
$key3 = ProductConfigurationService::configurationKey(100, [
    ['group_id' => 1, 'option_id' => 201]
]);

if ($key1 === $key2 && $key1 !== $key3) {
    echo "[PASS] configurationKey es determinístico y ordena opciones\n";
} else {
    echo "[FAIL] configurationKey: keys no son determinísticas\n";
    echo "  key1: $key1\n";
    echo "  key2: $key2\n";
    echo "  key3: $key3\n";
}

// Test 3: configurationKey genera hashes consistentes
$expectedMd5 = md5('100|1:200|2:300');
if ($key1 === $expectedMd5) {
    echo "[PASS] configurationKey genera md5 correcto\n";
} else {
    echo "[FAIL] configurationKey md5 incorrecto: esperado=$expectedMd5, actual=$key1\n";
}

echo "\n=== Todos los tests completados ===\n";
