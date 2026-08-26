-- ============================================================
-- Tu Inventario- Schema Completo v2.0
-- Compatible con SQLite.
-- Para PostgreSQL: cambiar AUTOINCREMENT→SERIAL, TEXT→VARCHAR, REAL→NUMERIC
-- ============================================================

-- Negocios / Inquilinos
CREATE TABLE IF NOT EXISTS businesses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_name TEXT NOT NULL,
    business_name TEXT NOT NULL,
    rif TEXT,
    owner_phone TEXT,
    business_phone TEXT,
    document_id TEXT NOT NULL, -- Cédula
    email TEXT NOT NULL,
    category TEXT NOT NULL, -- e.g. Comida, Repuestos, Vehículos, Inmuebles, Tecnología
    slug TEXT UNIQUE, -- Enlace amigable de la tienda (tienda/nombre-empresa)
    subscription_status TEXT DEFAULT 'trial',
    trial_ends_at DATETIME,
    logo_base64 TEXT,
    ticket_header TEXT,
    ticket_footer TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Usuarios y Roles (RBAC)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER,
    username TEXT NOT NULL UNIQUE,
    full_name TEXT,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'vendedor' CHECK(role IN ('super_admin','administrador','empleado','vendedor')),
    status INTEGER DEFAULT 1,
    permissions_json TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Categorías de Productos
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    name TEXT NOT NULL,
    description TEXT,
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Unidades de Medida
CREATE TABLE IF NOT EXISTS units_of_measure (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL, -- ej. 'Saco', 'Caja', 'Kilogramo', 'Gramo'
    abbreviation TEXT NOT NULL, -- ej. 'SC', 'CX', 'KG', 'G'
    base_type TEXT NOT NULL -- 'peso', 'volumen', 'unidad'
);

-- Marcas
CREATE TABLE IF NOT EXISTS brands (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    name TEXT NOT NULL,
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Productos
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    category_id INTEGER,
    brand_id INTEGER,
    supplier_id INTEGER,
    name TEXT NOT NULL,
    sku TEXT,
    barcode TEXT,
    
    -- Configuración de Costos
    cost_type TEXT DEFAULT 'unit', -- 'unit' (Costo por unidad) o 'bulk' (Costo por bulto)
    unit_cost REAL,
    bulk_cost REAL,
    units_per_bulk INTEGER DEFAULT 1,
    currency TEXT DEFAULT 'USD', -- 'USD', 'USD-BCV', 'VES', 'COP', 'EUR'
    
    -- Gestión de Precios
    profit_margin REAL DEFAULT 0.0, -- Porcentaje de ganancia (e.g. 30.0 para 30%)
    price REAL NOT NULL, -- Precio final pre-calculado para acceso rápido
    is_tax_exempt BOOLEAN DEFAULT 0, -- 0: Paga IVA, 1: Exento de IVA
    
    -- Inventario
    stock REAL DEFAULT 0, -- REAL para soportar peso (Kg) o fracciones
    unit_of_measure TEXT DEFAULT 'unidades', -- 'unidades', 'kg', 'litros', 'gramos'
    allow_fractional_sales BOOLEAN DEFAULT 0, -- 1: Permite decimales, 0: Solo enteros
    purchase_unit_id INTEGER, -- Ej: ID unidad de bulto/saco
    sale_unit_id INTEGER, -- Ej: ID unidad mínima/kg
    conversion_factor REAL DEFAULT 1.0, -- Multiplicador (Ej: 1 Saco = 50 Kg)
    min_stock REAL DEFAULT 5,
    image TEXT,
    
    -- Atributos Dinámicos Extendidos (Para Multi-Categoría Negocio)
    -- Para SQLite esto es TEXT que almacena un string JSON
    -- Para PostgreSQL este campo debiese castearse a JSONB
    dynamic_attributes TEXT,
    
    -- Restaurante / Platos
    is_dish BOOLEAN DEFAULT 0, -- 1: El producto es un plato elaborado con receta
    prep_time INTEGER, -- Tiempo de preparación en minutos
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Recetas de Platos (Ingredientes por Plato - Módulo Restaurante)
CREATE TABLE IF NOT EXISTS recipe_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    dish_id INTEGER NOT NULL,        -- FK products (el plato)
    ingredient_id INTEGER NOT NULL,  -- FK products (el insumo)
    quantity REAL NOT NULL,          -- Cantidad consumida (Ej: 0.350 kg de carne)
    unit_id INTEGER,                 -- FK units_of_measure (unidad de consumo)
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dish_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (ingredient_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units_of_measure(id),
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Atributos dinámicos relacionales legados EAV (Opcional si se requiere consulta 1st normal form)
CREATE TABLE IF NOT EXISTS product_meta (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER,
    meta_key TEXT NOT NULL,
    meta_value TEXT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Clientes
CREATE TABLE IF NOT EXISTS clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    name TEXT NOT NULL,
    document TEXT,
    phone TEXT,
    extra_phones TEXT,
    email TEXT,
    address TEXT,
    workplace TEXT,
    workplace_component TEXT,
    workplace_detail TEXT,
    workplace_address TEXT,
    monthly_income REAL,
    ip_address TEXT,
    user_agent TEXT,
    gps_location TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Proveedores
CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    name TEXT NOT NULL,
    contact_name TEXT,
    phone TEXT,
    email TEXT,
    address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Ventas
CREATE TABLE IF NOT EXISTS sales (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    client_id INTEGER,
    total REAL NOT NULL,
    cash_received REAL,
    change_given REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
);

-- Detalle de Ventas
CREATE TABLE IF NOT EXISTS sale_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sale_id INTEGER,
    product_id INTEGER,
    quantity INTEGER NOT NULL,
    price_at_sale REAL NOT NULL,
    cost_at_sale REAL DEFAULT 0,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- Compras / Recepción de Mercancía
CREATE TABLE IF NOT EXISTS purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier_id INTEGER,
    user_id INTEGER,
    total REAL NOT NULL DEFAULT 0,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Detalle de Compras
CREATE TABLE IF NOT EXISTS purchase_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchase_id INTEGER,
    product_id INTEGER,
    quantity INTEGER NOT NULL,
    unit_type TEXT DEFAULT 'unidad',
    cost_per_unit REAL NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- Gastos / Egresos
CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    category TEXT NOT NULL, -- 'Servicios', 'Nómina', 'Alquiler', 'Otro'
    description TEXT NOT NULL,
    amount REAL NOT NULL,
    expense_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Kardex - Trazabilidad de Movimientos de Inventario
CREATE TABLE IF NOT EXISTS kardex (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    type TEXT NOT NULL, -- 'entrada_compra', 'salida_venta', 'ajuste_positivo', 'ajuste_negativo', 'merma'
    quantity INTEGER NOT NULL,
    stock_after INTEGER,
    reference_type TEXT, -- 'sale', 'purchase', 'manual'
    reference_id INTEGER,
    note TEXT,
    user_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Auditoría
CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT,
    table_name TEXT,
    record_id INTEGER,
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Configuraciones globales y de la Empresa
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    category TEXT DEFAULT 'general', -- 'taxes', 'system', 'company', 'rates'
    tenant_id INTEGER,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Métodos de Pago Habilitados
CREATE TABLE IF NOT EXISTS payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    name TEXT NOT NULL, 
    code TEXT UNIQUE NOT NULL, -- Ej: 'usd_cash', 'bs_pos', 'bs_transfer', 'zelle'
    currency TEXT DEFAULT 'VES', -- 'VES', 'USD', 'COP'
    applies_igtf BOOLEAN DEFAULT 0, -- Si es 'usd_cash' aplica IGTF
    is_active BOOLEAN DEFAULT 1,
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Configuración Visual de la Tienda (Landing Page)
CREATE TABLE IF NOT EXISTS store_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    business_id INTEGER NOT NULL UNIQUE,
    hero_title TEXT,
    hero_subtitle TEXT,
    primary_color TEXT DEFAULT '#10b981',
    logo_url TEXT,
    whatsapp TEXT,
    instagram TEXT,
    facebook TEXT,
    tiktok TEXT,
    twitter TEXT,
    show_prices INTEGER DEFAULT 1,
    is_published INTEGER DEFAULT 1,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Arqueo de Caja (Sesiones de caja)
CREATE TABLE IF NOT EXISTS arqueo_caja (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    monto_inicial_usd REAL DEFAULT 0,
    monto_inicial_bs REAL DEFAULT 0,
    ventas_usd REAL DEFAULT 0,
    ventas_bs REAL DEFAULT 0,
    declarado_usd REAL DEFAULT 0,
    declarado_bs REAL DEFAULT 0,
    diferencia_usd REAL DEFAULT 0,
    diferencia_bs REAL DEFAULT 0,
    estado TEXT DEFAULT 'abierta' CHECK(estado IN ('abierta', 'cerrada')),
    fecha_apertura DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Créditos / Fiados (Cuentas por Cobrar)
CREATE TABLE IF NOT EXISTS credits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    client_id INTEGER NOT NULL,
    sale_id INTEGER,
    total_amount REAL NOT NULL,
    paid_amount REAL DEFAULT 0,
    remaining_amount REAL NOT NULL,
    due_date DATE,
    status TEXT DEFAULT 'activo' CHECK(status IN ('activo','pagado','atrasado','cancelado')),
    notes TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Historial de Abonos / Pagos de Crédito
CREATE TABLE IF NOT EXISTS credit_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    credit_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    payment_method TEXT,
    reference TEXT,
    status TEXT DEFAULT 'pendiente' CHECK(status IN ('pendiente','aprobado','rechazado')),
    approved_by INTEGER,
    approved_at DATETIME,
    notes TEXT,
    reported_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (credit_id) REFERENCES credits(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (reported_by) REFERENCES users(id)
);

-- Notificaciones del Sistema
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    target_role TEXT,
    target_user_id INTEGER,
    type TEXT NOT NULL,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    reference_type TEXT,
    reference_id INTEGER,
    is_read BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Pedidos de Tienda (Store Orders)
CREATE TABLE IF NOT EXISTS store_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    customer_name TEXT,
    customer_phone TEXT,
    customer_address TEXT,
    notes TEXT,
    payment_method TEXT,
    total_usd REAL,
    total_bs REAL,
    items_json TEXT,
    status TEXT DEFAULT 'pendiente' CHECK(status IN ('pendiente','despachado','cancelado')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Planes de Suscripción
CREATE TABLE IF NOT EXISTS plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price REAL NOT NULL,
    duration_days INTEGER NOT NULL,
    features_json TEXT
);

-- Registro de Pagos / Reportes Binance-BDV
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id INTEGER,
    plan_id INTEGER,
    amount REAL NOT NULL,
    payment_method TEXT NOT NULL,
    reference_number TEXT NOT NULL,
    proof_image TEXT,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);

-- Suscripciones Web Push API
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    role TEXT,
    endpoint TEXT NOT NULL UNIQUE,
    p256dh TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Índices de Rendimiento y Aislamiento Multi-Tenant
CREATE UNIQUE INDEX IF NOT EXISTS idx_cat_tenant_name ON categories(tenant_id, name);
CREATE UNIQUE INDEX IF NOT EXISTS idx_brand_tenant_name ON brands(tenant_id, name);
CREATE UNIQUE INDEX IF NOT EXISTS idx_prod_tenant_sku ON products(tenant_id, sku) WHERE sku IS NOT NULL AND sku != '';
CREATE UNIQUE INDEX IF NOT EXISTS idx_prod_tenant_code ON products(tenant_id, barcode) WHERE barcode IS NOT NULL AND barcode != '';

-- Índices de Performance Solicitados
CREATE INDEX IF NOT EXISTS idx_created_at_sales ON sales(created_at);
CREATE INDEX IF NOT EXISTS idx_tenant_id_products ON products(tenant_id);

