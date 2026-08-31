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

    // 4. CREAR NEGOCIO PARA DEMO (id = 1)
    $stmtBiz = $db->prepare("INSERT INTO businesses (owner_name, business_name, rif, owner_phone, business_phone, document_id, email, category, slug) VALUES ('Demo', 'Negocio Demo', 'J-00000000-0', '0000', '0000', '00000000', 'demo@tuinventario.app', 'general', 'negocio-demo')");
    $stmtBiz->execute();
    $biz_id = $db->lastInsertId();

    // 5. INSERTAR AL USUARIO DEMO (id = 2) atado al negocio demo
    $hashDemo = password_hash('demo12345', PASSWORD_DEFAULT);
    $stmtDemo = $db->prepare("INSERT INTO users (business_id, username, full_name, password, role, status) VALUES (?, '00000000', 'Usuario Demo', ?, 'administrador', 1)");
    $stmtDemo->execute([$biz_id, $hashDemo]);
    echo "¡Cuenta Demo (00000000) generada con éxito dentro del Negocio Demo!\n";
    
    // Repoblar las configuraciones/planes por defecto
    require_once __DIR__ . '/../database/Migration.php';
    Migration::ensureTablesExist($db);
    echo "Configuraciones base (Settings, Payment Methods) re-sembradas con éxito.\n";

} catch (PDOException $e) {
    die("Error al resetear la base de datos: " . $e->getMessage());
}
