<?php
/**
 * Migration.php - Auto-Migración Inteligente para TuInventarioApp
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
            
            // Migrar la llave foranea de recipe_items para que apunte a products en vez de kitchen_ingredients
            try {
                $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                if ($driver === 'pgsql') {
                    $pdo->exec("ALTER TABLE recipe_items DROP CONSTRAINT IF EXISTS recipe_items_ingredient_id_fkey");
                    $pdo->exec("ALTER TABLE recipe_items ADD CONSTRAINT recipe_items_ingredient_id_fkey FOREIGN KEY (ingredient_id) REFERENCES products(id) ON DELETE CASCADE");
                }
            } catch (\Exception $e) { }

            // Paso 2: Verificar y agregar columnas faltantes en tablas existentes
            self::ensureColumns($pdo);
            
            // Paso 3: Insertar datos por defecto si están vacíos
            self::seedDefaults($pdo);
            
            // Paso 4: Backfill de slugs para tiendas existentes
            self::backfillSlugs($pdo);
            
            // Paso 5: Backfill Data
            self::backfillSaaSData($pdo);
            
            // Paso 6: Backfill Data for Presentations Migration
            self::backfillPresentations($pdo);
            
            self::$executed = true;
        } catch (PDOException $e) {
            error_log('[Migration] Error: ' . $e->getMessage());
        }
    }

    /**
     * Ejecuta el schema.sql completo.
     */
    private static function runSchema(PDO $pdo) {
        $schemaPath = __DIR__ . '/schema_postgres.sql';
        if (!file_exists($schemaPath)) return;

        $sql = file_get_contents($schemaPath);
        $sql = preg_replace('/--.*$/m', '', $sql); // Limpiar comentarios
        
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($s) { return !empty($s); }
        );

        foreach ($statements as $statement) {
            
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Si una tabla falla, registramos y continuamos con las demás
                error_log('[Migration] Error executing statement: ' . $e->getMessage() . ' | SQL: ' . $statement);
            }
        }
        
        // Ensure standalone QR menu table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS free_qr_menus (
            slug TEXT PRIMARY KEY,
            edit_code TEXT,
            menu_base64 TEXT,
            menu_type TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Ejecutar los índices requeridos que pudiesen haber fallado en el parsing simple
        try { $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_cat_tenant_name ON categories(tenant_id, name)"); } catch (\Exception $e) {}
        try { $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_brand_tenant_name ON brands(tenant_id, name)"); } catch (\Exception $e) {}
        try { 
            $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_prod_tenant_sku ON products(tenant_id, sku) WHERE sku IS NOT NULL AND sku != ''");
            $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_prod_tenant_code ON products(tenant_id, barcode) WHERE barcode IS NOT NULL AND barcode != ''");
        } catch (\Exception $e) {}
        try { $pdo->exec("CREATE INDEX IF NOT EXISTS idx_created_at_sales ON sales(created_at)"); } catch (\Exception $e) {}
        try { $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tenant_id_products ON products(tenant_id)"); } catch (\Exception $e) {}
    }

    /**
     * Verifica que columnas críticas existan en tablas pre-existentes.
     * PostgreSQL soporta ADD COLUMN IF NOT EXISTS, pero usamos check por compatibilidad.
     */
    private static function ensureColumns(PDO $pdo) {
        $requiredColumns = [
            'businesses' => [
                'slug' => "ALTER TABLE businesses ADD COLUMN slug TEXT",
                'subscription_status' => "ALTER TABLE businesses ADD COLUMN subscription_status TEXT DEFAULT 'trial'",
                'trial_ends_at' => "ALTER TABLE businesses ADD COLUMN trial_ends_at DATETIME",
                'logo_base64' => "ALTER TABLE businesses ADD COLUMN logo_base64 TEXT",
                'ticket_header' => "ALTER TABLE businesses ADD COLUMN ticket_header TEXT",
                'ticket_footer' => "ALTER TABLE businesses ADD COLUMN ticket_footer TEXT",
                'menu_file_base64' => "ALTER TABLE businesses ADD COLUMN menu_file_base64 TEXT",
                'menu_file_type' => "ALTER TABLE businesses ADD COLUMN menu_file_type TEXT"
            ],
            'settings' => [
                'category' => "ALTER TABLE settings ADD COLUMN category TEXT DEFAULT 'general'",
                'updated_at' => "ALTER TABLE settings ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP",
                'tenant_id'  => "ALTER TABLE settings ADD COLUMN tenant_id INTEGER",
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
                'allow_fractional_sales' => "ALTER TABLE products ADD COLUMN allow_fractional_sales BOOLEAN DEFAULT 0",
                'purchase_unit_id' => "ALTER TABLE products ADD COLUMN purchase_unit_id INTEGER",
                'sale_unit_id' => "ALTER TABLE products ADD COLUMN sale_unit_id INTEGER",
                'conversion_factor' => "ALTER TABLE products ADD COLUMN conversion_factor REAL DEFAULT 1.0",
                'supplier_id'    => "ALTER TABLE products ADD COLUMN supplier_id INTEGER",
                'tenant_id'      => "ALTER TABLE products ADD COLUMN tenant_id INTEGER",
                'dynamic_attributes' => "ALTER TABLE products ADD COLUMN dynamic_attributes TEXT",
                'is_dish'        => "ALTER TABLE products ADD COLUMN is_dish BOOLEAN DEFAULT 0",
                'prep_time'      => "ALTER TABLE products ADD COLUMN prep_time INTEGER",
            ],
            'users' => [
                'business_id'    => "ALTER TABLE users ADD COLUMN business_id INTEGER",
                'full_name'      => "ALTER TABLE users ADD COLUMN full_name TEXT",
                'permissions_json'=> "ALTER TABLE users ADD COLUMN permissions_json TEXT",
            ],
            'categories' => [
                'tenant_id'      => "ALTER TABLE categories ADD COLUMN tenant_id INTEGER",
            ],
            'brands' => [
                'tenant_id'      => "ALTER TABLE brands ADD COLUMN tenant_id INTEGER",
            ],
            'clients' => [
                'tenant_id'           => "ALTER TABLE clients ADD COLUMN tenant_id INTEGER",
                'extra_phones'        => "ALTER TABLE clients ADD COLUMN extra_phones TEXT",
                'workplace'           => "ALTER TABLE clients ADD COLUMN workplace TEXT",
                'workplace_component' => "ALTER TABLE clients ADD COLUMN workplace_component TEXT",
                'workplace_detail'    => "ALTER TABLE clients ADD COLUMN workplace_detail TEXT",
                'workplace_address'   => "ALTER TABLE clients ADD COLUMN workplace_address TEXT",
                'monthly_income'      => "ALTER TABLE clients ADD COLUMN monthly_income REAL",
                'ip_address'          => "ALTER TABLE clients ADD COLUMN ip_address TEXT",
                'user_agent'          => "ALTER TABLE clients ADD COLUMN user_agent TEXT",
                'gps_location'        => "ALTER TABLE clients ADD COLUMN gps_location TEXT",
            ],
            'suppliers' => [
                'tenant_id'      => "ALTER TABLE suppliers ADD COLUMN tenant_id INTEGER",
            ],
            'payment_methods' => [
                'tenant_id'      => "ALTER TABLE payment_methods ADD COLUMN tenant_id INTEGER",
            ],
            'sales' => [
                'subtotal'  => "ALTER TABLE sales ADD COLUMN subtotal REAL DEFAULT 0",
                'iva'       => "ALTER TABLE sales ADD COLUMN iva REAL DEFAULT 0",
                'igtf'      => "ALTER TABLE sales ADD COLUMN igtf REAL DEFAULT 0",
            ],
            'sale_items' => [
                'cost_at_sale' => "ALTER TABLE sale_items ADD COLUMN cost_at_sale REAL DEFAULT 0",
            ],
            'purchase_items' => [
                'unit_type' => "ALTER TABLE purchase_items ADD COLUMN unit_type TEXT DEFAULT 'unidad'",
                'presentation_id' => "ALTER TABLE purchase_items ADD COLUMN presentation_id INTEGER",
            ],
            'store_config' => [
                'facebook' => "ALTER TABLE store_config ADD COLUMN facebook TEXT",
                'tiktok' => "ALTER TABLE store_config ADD COLUMN tiktok TEXT",
                'twitter' => "ALTER TABLE store_config ADD COLUMN twitter TEXT",
                'store_name' => "ALTER TABLE store_config ADD COLUMN store_name TEXT",
                'background_image' => "ALTER TABLE store_config ADD COLUMN background_image TEXT",
            ],
            'credits' => [
                'tenant_id'        => "ALTER TABLE credits ADD COLUMN tenant_id INTEGER",
                'paid_amount'      => "ALTER TABLE credits ADD COLUMN paid_amount REAL DEFAULT 0",
                'remaining_amount' => "ALTER TABLE credits ADD COLUMN remaining_amount REAL",
                'due_date'         => "ALTER TABLE credits ADD COLUMN due_date DATE",
                'status'           => "ALTER TABLE credits ADD COLUMN status TEXT DEFAULT 'activo'",
                'notes'            => "ALTER TABLE credits ADD COLUMN notes TEXT",
                'created_by'       => "ALTER TABLE credits ADD COLUMN created_by INTEGER",
                'updated_at'       => "ALTER TABLE credits ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP",
            ],
            'credit_payments' => [
                'payment_method' => "ALTER TABLE credit_payments ADD COLUMN payment_method TEXT",
                'reference'      => "ALTER TABLE credit_payments ADD COLUMN reference TEXT",
                'status'         => "ALTER TABLE credit_payments ADD COLUMN status TEXT DEFAULT 'pendiente'",
                'approved_by'    => "ALTER TABLE credit_payments ADD COLUMN approved_by INTEGER",
                'approved_at'    => "ALTER TABLE credit_payments ADD COLUMN approved_at DATETIME",
                'notes'          => "ALTER TABLE credit_payments ADD COLUMN notes TEXT",
                'reported_by'    => "ALTER TABLE credit_payments ADD COLUMN reported_by INTEGER",
            ],
            'notifications' => [
                'tenant_id'       => "ALTER TABLE notifications ADD COLUMN tenant_id INTEGER",
                'target_role'     => "ALTER TABLE notifications ADD COLUMN target_role TEXT",
                'target_user_id'  => "ALTER TABLE notifications ADD COLUMN target_user_id INTEGER",
                'reference_type'  => "ALTER TABLE notifications ADD COLUMN reference_type TEXT",
                'reference_id'    => "ALTER TABLE notifications ADD COLUMN reference_id INTEGER",
                'is_read'         => "ALTER TABLE notifications ADD COLUMN is_read BOOLEAN DEFAULT FALSE",
            ],
            'kitchen_ingredients' => [
                'image'           => "ALTER TABLE kitchen_ingredients ADD COLUMN image TEXT",
            ],
        ];

        foreach ($requiredColumns as $table => $columns) {
            // Obtener columnas actuales de la tabla
            $existingCols = [];
            try {
                $result = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}'");
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $existingCols[] = $row['column_name'];
                }
            } catch (PDOException $e) {
                continue; // La tabla no existe aún, será creada por runSchema
            }

            foreach ($columns as $colName => $alterSql) {
                if (!in_array($colName, $existingCols)) {
                    $alterSql = str_ireplace('DATETIME', 'TIMESTAMP', $alterSql);
                    $alterSql = str_ireplace('BOOLEAN DEFAULT 0', 'BOOLEAN DEFAULT FALSE', $alterSql);
                    $alterSql = str_ireplace('BOOLEAN DEFAULT 1', 'BOOLEAN DEFAULT TRUE', $alterSql);
                    try {
                        $pdo->exec($alterSql);
                    } catch (PDOException $e) {
                        // Columna ya existe o error menor, ignorar
                        error_log("[Migration] ALTER: {$e->getMessage()}");
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
            ['bcv_rate',       '791.32',    'rates'],
            ['parallel_rate',  '0',         'rates'],
            ['cop_rate',       '0',         'rates'],
            ['tax_iva',        '16',        'fiscal'],
            ['tax_igtf',       '3',         'fiscal'],
            ['calc_method',    'fiscal',    'fiscal'],
            ['iva_method',     'included',  'fiscal'],
            ['business_name',  'TuInventarioApp', 'company'],
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
                ['Biopago (BDV)',   'bs_biopago', 'VES', 0],
                ['EUR Efectivo',    'eur_cash',   'EUR', 1],
                ['Zelle',           'zelle',      'USD', 1],
            ];
            foreach ($methods as $m) {
                $payInsert->execute($m);
            }
        }

        // Planes por defecto
        try {
            $planCheck = $pdo->query("SELECT COUNT(*) FROM plans")->fetchColumn();
            if ($planCheck == 0) {
                $planInsert = $pdo->prepare("INSERT INTO plans (name, price, duration_days, features_json) VALUES (?, ?, ?, ?)");
                $plans = [
                    ['Plan Básico', 10.00, 30, '{"limit_users": 2, "limit_products": 100, "custom_module": true}'],
                    ['Plan Anual', 199.00, 365, '{"limit_users": 4, "limit_products": 200, "custom_module": true}']
                ];
                foreach ($plans as $p) {
                    $planInsert->execute($p);
                }
            }
        } catch (PDOException $e) {
            // Ignorar si la tabla no se ha creado aún en un edge-case
        }
    }

    /**
     * Backfill slugs for existing businesses that don't have one
     */
    private static function backfillSlugs(PDO $pdo) {
        try {
            $stmt = $pdo->query("SELECT id, business_name FROM businesses WHERE slug IS NULL OR slug = ''");
            $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($businesses)) return;
            
            $updateStmt = $pdo->prepare("UPDATE businesses SET slug = ? WHERE id = ?");
            $checkStmt = $pdo->prepare("SELECT id FROM businesses WHERE slug = ? AND id != ?");
            
            foreach ($businesses as $b) {
                // Generate base slug
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $b['business_name']), '-'));
                if (empty($slug)) $slug = 'tienda-' . $b['id'];
                
                // Ensure uniqueness
                $originalSlug = $slug;
                $counter = 1;
                while (true) {
                    $checkStmt->execute([$slug, $b['id']]);
                    if (!$checkStmt->fetch()) break; // Unique!
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                $updateStmt->execute([$slug, $b['id']]);
            }
        } catch (PDOException $e) {
            error_log('[Migration] Error in backfillSlugs: ' . $e->getMessage());
        }
    }

    /**
     * Backfill data for businesses (trial_ends_at, plan_id, etc)
     */
    private static function backfillSaaSData(PDO $pdo) {
        try {
            // Update prices and features for existing plans to match new requirements
            $pdo->exec("UPDATE plans SET name = 'Plan Básico', price = 10.00, features_json = '{\"limit_users\": 2, \"limit_products\": 100, \"custom_module\": true}' WHERE id = 1");
            $pdo->exec("UPDATE plans SET name = 'Plan Anual', price = 199.00, features_json = '{\"limit_users\": 4, \"limit_products\": 200, \"custom_module\": true}' WHERE id = 2");
            // Eliminar planes Anuales duplicados creados por migraciones anteriores
            $pdo->exec("DELETE FROM plans WHERE name = 'Plan Anual' AND id != 2");

            $pdo->exec("UPDATE businesses SET trial_ends_at = CURRENT_TIMESTAMP + INTERVAL '30 days' WHERE trial_ends_at IS NULL");
        } catch (PDOException $e) {
            error_log('[Migration] Error in backfillData: ' . $e->getMessage());
        }
    }

    /**
     * Backfill data for Product Presentations from the old internal columns.
     */
    private static function backfillPresentations(PDO $pdo) {
        try {
            // Revisar si ya existen presentaciones (asumimos que si hay más de 0, ya migró).
            $check = $pdo->query("SELECT COUNT(*) FROM product_presentations")->fetchColumn();
            if ($check > 0) return;

            // Busca los productos que necesitan migrar su unidad al nuevo schema.
            $stmt = $pdo->query("SELECT id, name, unit_of_measure, content_per_purchase, contained_unit_id FROM products");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($products)) return;

            $insertStmt = $pdo->prepare("INSERT INTO product_presentations (product_id, name, quantity, unit_id) VALUES (?, ?, ?, ?)");
            
            foreach ($products as $p) {
                // Generar un registro por default basado en los datos antigüos o inventar uno si fallaba
                $pr_name = empty($p['unit_of_measure']) ? "Presentación Principal" : $p['unit_of_measure'];
                $pr_qty  = empty($p['content_per_purchase']) ? 1 : $p['content_per_purchase'];
                $pr_unit = empty($p['contained_unit_id']) ? null : $p['contained_unit_id'];
                
                $insertStmt->execute([$p['id'], $pr_name, $pr_qty, $pr_unit]);
            }
            
            error_log('[Migration] Product Presentations Backfilled successfully.');
        } catch (PDOException $e) {
            error_log('[Migration] Error in backfillPresentations: ' . $e->getMessage());
        }
    }
}
