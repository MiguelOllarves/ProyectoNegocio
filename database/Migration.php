<?php
/**
 * Migration.php - Auto-Migración Inteligente para TuInventarioApp ERP
 * 
 * Se ejecuta automáticamente al conectar con la BD.
 * 1. Crea tablas faltantes desde schema.sql
 * 2. Verifica columnas faltantes en tablas existentes y las agrega (ALTER TABLE)
 * 3. Siembra datos por defecto si las tablas están vacías
 */
class Migration {
    
    private static $executed = false;

    /**
     * Punto de entrada principal.
     */
    public static function ensureTablesExist(PDO $pdo) {
        if (self::$executed) return;
        
        try {
            // Paso 1: Ejecutar schema.sql (CREATE IF NOT EXISTS - seguro para re-ejecución)
            self::runSchema($pdo);
            
            // Paso 2: Verificar y agregar columnas faltantes en tablas existentes
            self::ensureColumns($pdo);
            
            // Paso 3: Insertar datos por defecto si están vacíos
            self::seedDefaults($pdo);
            
            self::$executed = true;
        } catch (PDOException $e) {
            error_log('[ERP Migration] Error: ' . $e->getMessage());
        }
    }

    /**
     * Ejecuta el schema.sql completo.
     */
    private static function runSchema(PDO $pdo) {
        $schemaPath = __DIR__ . '/schema.sql';
        if (!file_exists($schemaPath)) return;

        $sql = file_get_contents($schemaPath);
        $sql = preg_replace('/--.*$/m', '', $sql); // Limpiar comentarios
        
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($s) { return !empty($s); }
        );

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }
    }

    /**
     * Verifica que columnas críticas existan en tablas pre-existentes.
     * SQLite solo soporta ADD COLUMN, no MODIFY ni DROP.
     */
    private static function ensureColumns(PDO $pdo) {
        $requiredColumns = [
            'settings' => [
                'category' => "ALTER TABLE settings ADD COLUMN category TEXT DEFAULT 'general'",
                'updated_at' => "ALTER TABLE settings ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP",
            ],
            'products' => [
                'cost_type'      => "ALTER TABLE products ADD COLUMN cost_type TEXT DEFAULT 'unit'",
                'unit_cost'      => "ALTER TABLE products ADD COLUMN unit_cost REAL",
                'bulk_cost'      => "ALTER TABLE products ADD COLUMN bulk_cost REAL",
                'units_per_bulk' => "ALTER TABLE products ADD COLUMN units_per_bulk INTEGER DEFAULT 1",
                'currency'       => "ALTER TABLE products ADD COLUMN currency TEXT DEFAULT 'USD'",
                'profit_margin'  => "ALTER TABLE products ADD COLUMN profit_margin REAL DEFAULT 0.0",
                'is_tax_exempt'  => "ALTER TABLE products ADD COLUMN is_tax_exempt BOOLEAN DEFAULT 0",
                'unit_of_measure'=> "ALTER TABLE products ADD COLUMN unit_of_measure TEXT DEFAULT 'unidades'",
                'supplier_id'    => "ALTER TABLE products ADD COLUMN supplier_id INTEGER",
            ],
            'sales' => [
                'subtotal'  => "ALTER TABLE sales ADD COLUMN subtotal REAL DEFAULT 0",
                'iva'       => "ALTER TABLE sales ADD COLUMN iva REAL DEFAULT 0",
                'igtf'      => "ALTER TABLE sales ADD COLUMN igtf REAL DEFAULT 0",
            ],
        ];

        foreach ($requiredColumns as $table => $columns) {
            // Obtener columnas actuales de la tabla
            $existingCols = [];
            try {
                $result = $pdo->query("PRAGMA table_info({$table})");
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $existingCols[] = $row['name'];
                }
            } catch (PDOException $e) {
                continue; // La tabla no existe aún, será creada por runSchema
            }

            foreach ($columns as $colName => $alterSql) {
                if (!in_array($colName, $existingCols)) {
                    try {
                        $pdo->exec($alterSql);
                    } catch (PDOException $e) {
                        // Columna ya existe o error menor, ignorar
                        error_log("[ERP Migration] ALTER: {$e->getMessage()}");
                    }
                }
            }
        }
    }

    /**
     * Inserta configuraciones y métodos de pago por defecto si están vacíos.
     */
    private static function seedDefaults(PDO $pdo) {
        // Settings por defecto
        $defaults = [
            ['bcv_rate',       '622.21',    'rates'],
            ['parallel_rate',  '0',         'rates'],
            ['cop_rate',       '0',         'rates'],
            ['tax_iva',        '16',        'fiscal'],
            ['tax_igtf',       '3',         'fiscal'],
            ['calc_method',    'fiscal',    'fiscal'],
            ['iva_method',     'included',  'fiscal'],
            ['business_name',  'TuInventarioApp ERP', 'company'],
            ['business_logo',  '',          'company'],
        ];

        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE key = ?");
        $insertStmt = $pdo->prepare("INSERT INTO settings (key, value, category) VALUES (?, ?, ?)");

        foreach ($defaults as [$key, $value, $category]) {
            $checkStmt->execute([$key]);
            if ($checkStmt->fetchColumn() == 0) {
                $insertStmt->execute([$key, $value, $category]);
            }
        }

        // Métodos de pago por defecto
        $payCheck = $pdo->query("SELECT COUNT(*) FROM payment_methods")->fetchColumn();
        if ($payCheck == 0) {
            $payInsert = $pdo->prepare("INSERT INTO payment_methods (name, code, currency, applies_igtf, is_active) VALUES (?, ?, ?, ?, 1)");
            $methods = [
                ['USD Efectivo',    'usd_cash',   'USD', 1],
                ['BS Efectivo',     'bs_cash',    'VES', 0],
                ['BS Pago Móvil',   'bs_pm',      'VES', 0],
                ['BS Punto Venta',  'bs_pos',     'VES', 0],
                ['EUR Efectivo',    'eur_cash',   'EUR', 1],
                ['Zelle',           'zelle',      'USD', 1],
            ];
            foreach ($methods as $m) {
                $payInsert->execute($m);
            }
        }
    }
}
