-- ============================================================
-- TuInventarioApp ERP - Schema Completo v2.0
-- Compatible con SQLite.
-- Para PostgreSQL: cambiar AUTOINCREMENT→SERIAL, TEXT→VARCHAR, REAL→NUMERIC
-- ============================================================

-- Usuarios y Roles
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    full_name TEXT,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'cajero' CHECK(role IN ('admin','cajero')),
    status INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Categorías de Productos
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    description TEXT
);

-- Marcas
CREATE TABLE IF NOT EXISTS brands (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

-- Productos
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER,
    brand_id INTEGER,
    supplier_id INTEGER,
    name TEXT NOT NULL,
    sku TEXT UNIQUE,
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
    min_stock REAL DEFAULT 5,
    image TEXT,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- Atributos dinámicos de productos (IMEI, Talla, Color, Unidad, etc.)
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
    name TEXT NOT NULL,
    document TEXT,
    phone TEXT,
    email TEXT,
    address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Proveedores
CREATE TABLE IF NOT EXISTS suppliers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    contact_name TEXT,
    phone TEXT,
    email TEXT,
    address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Métodos de Pago Habilitados
CREATE TABLE IF NOT EXISTS payment_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL, 
    code TEXT UNIQUE NOT NULL, -- Ej: 'usd_cash', 'bs_pos', 'bs_transfer', 'zelle'
    currency TEXT DEFAULT 'VES', -- 'VES', 'USD', 'COP'
    applies_igtf BOOLEAN DEFAULT 0, -- Si es 'usd_cash' aplica IGTF
    is_active BOOLEAN DEFAULT 1
);
