<?php
require_once __DIR__ . '/../config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. OBTENER TODAS LAS TABLAS DEL ESQUEMA PUBLIC
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        die("No se encontraron tablas de base de datos para borrar.");
    }

    echo "Limpiando base de datos. Tablas encontradas: " . implode(', ', $tables) . "\n";

    // 2. HACER TRUNCATE A TODAS RESTART IDENTITY CASCADE
    $db->exec("TRUNCATE TABLE " . implode(', ', array_map(function($t) { return '"' . $t . '"'; }, $tables)) . " RESTART IDENTITY CASCADE");
    
    echo "¡Base de datos limpiada a 0 correctamente!\n";

    // 3. INSERTAR AL SUPER ADMIN (id = 1)
    $hashSuperAdmin = password_hash('Maom.18224757', PASSWORD_DEFAULT);
    $stmtSA = $db->prepare("INSERT INTO users (business_id, username, full_name, password, role, status) VALUES (NULL, '182247576', 'Super Administrador', ?, 'super_admin', 1)");
    $stmtSA->execute([$hashSuperAdmin]);
    echo "¡Cuenta Super Admin (182247576) generada con éxito!\n";

    // (Demo Business and Demo User creation removed by request)
    require_once __DIR__ . '/../database/Migration.php';
    Migration::ensureTablesExist($db);
    echo "Configuraciones base (Settings, Payment Methods) re-sembradas con éxito.\n";

} catch (PDOException $e) {
    die("Error al resetear la base de datos: " . $e->getMessage());
}
