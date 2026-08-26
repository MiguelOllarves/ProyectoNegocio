<?php
require_once __DIR__ . '/../config/Database.php';

echo "Aplicando schema v2.0...\n";
$db = Database::getInstance()->getConnection();

$schema = file_get_contents(__DIR__ . '/schema.sql');
$statements = array_filter(array_map('trim', explode(';', $schema)));

foreach ($statements as $s) {
    if (!empty($s)) {
        try {
            $db->exec($s);
            echo "  OK: " . substr($s, 0, 60) . "...\n";
        } catch (Exception $e) {
            echo "  SKIP: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nSchema actualizado correctamente.\n";
