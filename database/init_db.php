<?php
require_once __DIR__ . '/../config/Database.php';

echo "Inicializando la base de datos SQLite...\n";

try {
    $db = Database::getInstance()->getConnection();
    
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        die("Error: No se encontro el archivo schema.sql\n");
    }
    
    $sql = file_get_contents($schemaFile);
    $db->exec($sql);
    
    // Crear un usuario administrador inicial si no existe
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('admin123', PASSWORD_BCRYPT);
        $insert = $db->prepare("INSERT INTO users (username, password, role) VALUES ('admin', :pass, 'admin')");
        $insert->execute(['pass' => $password]);
        echo "Usuario administrador creado (Usuario: admin | Contraseña: admin123).\n";
    }

    echo "Base de datos inicializada correctamente en: " . DB_PATH . "\n";
} catch (Exception $e) {
    die("Error al inicializar la base de datos: " . $e->getMessage() . "\n");
}
