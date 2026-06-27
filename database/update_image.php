<?php
require_once __DIR__ . '/../config/Database.php';
try {
    $db = Database::getInstance()->getConnection();
    $db->exec("ALTER TABLE products ADD COLUMN image TEXT");
    echo 'Columna image añadida exitosamente';
} catch (Exception $e) {
    echo 'Ignorado (quizás ya existe): ' . $e->getMessage();
}
