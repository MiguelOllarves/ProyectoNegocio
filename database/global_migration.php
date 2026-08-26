<?php
require_once __DIR__ . '/../config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    echo "--- INICIANDO MIGRACION ---\n";

    // 1. Agregar columnas a businesses
    $columns = [
        "slug VARCHAR(255)",
        "subscription_status VARCHAR(50) DEFAULT 'trial'",
        "trial_ends_at TIMESTAMP"
    ];
    
    foreach ($columns as $col) {
        try {
            $db->exec("ALTER TABLE businesses ADD COLUMN " . $col);
        } catch (Exception $e) {
            // Ignorar si la columna ya existe
        }
    }
    echo "Columnas agregadas a businesses (si no existían).\n";

    // Update existing businesses with trial period (give them 30 days from now as a courtesy)
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $db->exec("UPDATE businesses SET trial_ends_at = datetime('now', '+30 days') WHERE trial_ends_at IS NULL");
    } else {
        $db->exec("UPDATE businesses SET trial_ends_at = CURRENT_TIMESTAMP + INTERVAL '30 days' WHERE trial_ends_at IS NULL");
    }
    
    // 2. Crear tabla plans
    $db->exec("CREATE TABLE IF NOT EXISTS plans (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        duration_days INTEGER NOT NULL,
        features_json TEXT
    )");
    echo "Tabla plans creada.\n";

    // Check if basic plan exists, if not, insert dummy plans
    $stmt = $db->query("SELECT id FROM plans LIMIT 1");
    if (!$stmt->fetch()) {
        $db->exec("INSERT INTO plans (name, price, duration_days, features_json) VALUES 
            ('Plan Básico', 10.00, 30, '{\"limit_users\": 2, \"limit_products\": 100, \"custom_module\": true}'),
            ('Plan Anual', 199.00, 365, '{\"limit_users\": 4, \"limit_products\": 200, \"custom_module\": true}')
        ");
        echo "Planes base insertados.\n";
    }

    // 3. Crear tabla payments
    $db->exec("CREATE TABLE IF NOT EXISTS payments (
        id SERIAL PRIMARY KEY,
        tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
        plan_id INTEGER REFERENCES plans(id),
        amount DECIMAL(10, 2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        reference_number VARCHAR(100) NOT NULL,
        proof_image TEXT,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Tabla payments creada.\n";

    $db->commit();
    echo "--- MIGRACION COMPLETADA CON EXITO ---\n";
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "ERROR FATAL: " . $e->getMessage() . "\n";
}
