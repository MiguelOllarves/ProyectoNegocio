-- ==============================================================================
-- SCHEMA PARA POSTGRESQL (SUPABASE / VERCEL)
-- ==============================================================================

-- Usuarios y Roles
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    full_name VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'cajero' CHECK(role IN ('admin','cajero')),
    status INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categorías de Productos
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT
);

-- Marcas
CREATE TABLE IF NOT EXISTS brands (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- Proveedores
CREATE TABLE IF NOT EXISTS suppliers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clientes
CREATE TABLE IF NOT EXISTS clients (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    document VARCHAR(50),
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Productos
CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    brand_id INTEGER REFERENCES brands(id) ON DELETE SET NULL,
    supplier_id INTEGER REFERENCES suppliers(id) ON DELETE SET NULL,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) UNIQUE,
    barcode VARCHAR(100),
    
    cost_type VARCHAR(50) DEFAULT 'unit',
    unit_cost NUMERIC(15,2),
    bulk_cost NUMERIC(15,2),
    units_per_bulk INTEGER DEFAULT 1,
    currency VARCHAR(10) DEFAULT 'USD',
    
    profit_margin NUMERIC(15,2) DEFAULT 0.0,
    price NUMERIC(15,2) NOT NULL,
    is_tax_exempt BOOLEAN DEFAULT FALSE,
    
    stock NUMERIC(15,2) DEFAULT 0,
    unit_of_measure VARCHAR(50) DEFAULT 'unidades',
    min_stock NUMERIC(15,2) DEFAULT 5,
    image TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Atributos dinámicos de productos
CREATE TABLE IF NOT EXISTS product_meta (
    id SERIAL PRIMARY KEY,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    meta_key VARCHAR(255) NOT NULL,
    meta_value TEXT
);

-- Ventas
CREATE TABLE IF NOT EXISTS sales (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    client_id INTEGER REFERENCES clients(id) ON DELETE SET NULL,
    total NUMERIC(15,2) NOT NULL,
    subtotal NUMERIC(15,2) DEFAULT 0,
    iva NUMERIC(15,2) DEFAULT 0,
    igtf NUMERIC(15,2) DEFAULT 0,
    cash_received NUMERIC(15,2),
    change_given NUMERIC(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Detalle de Ventas
CREATE TABLE IF NOT EXISTS sale_items (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER REFERENCES sales(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE RESTRICT,
    quantity INTEGER NOT NULL,
    price_at_sale NUMERIC(15,2) NOT NULL
);

-- Compras
CREATE TABLE IF NOT EXISTS purchases (
    id SERIAL PRIMARY KEY,
    supplier_id INTEGER REFERENCES suppliers(id) ON DELETE SET NULL,
    user_id INTEGER REFERENCES users(id),
    total NUMERIC(15,2) NOT NULL DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Detalle de Compras
CREATE TABLE IF NOT EXISTS purchase_items (
    id SERIAL PRIMARY KEY,
    purchase_id INTEGER REFERENCES purchases(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE RESTRICT,
    quantity INTEGER NOT NULL,
    cost_per_unit NUMERIC(15,2) NOT NULL
);

-- Gastos / Egresos
CREATE TABLE IF NOT EXISTS expenses (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    category VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    amount NUMERIC(15,2) NOT NULL,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Arqueo de Caja (Cashbox)
CREATE TABLE IF NOT EXISTS arqueo_caja (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    monto_inicial_usd NUMERIC(15,2) DEFAULT 0,
    monto_inicial_bs NUMERIC(15,2) DEFAULT 0,
    ventas_usd NUMERIC(15,2) DEFAULT 0,
    ventas_bs NUMERIC(15,2) DEFAULT 0,
    declarado_usd NUMERIC(15,2),
    declarado_bs NUMERIC(15,2),
    diferencia_usd NUMERIC(15,2),
    diferencia_bs NUMERIC(15,2),
    estado VARCHAR(50) DEFAULT 'abierta',
    fecha_apertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre TIMESTAMP
);

-- Kardex
CREATE TABLE IF NOT EXISTS kardex (
    id SERIAL PRIMARY KEY,
    product_id INTEGER REFERENCES products(id),
    type VARCHAR(50) NOT NULL,
    quantity NUMERIC(15,2) NOT NULL,
    stock_after NUMERIC(15,2),
    reference_type VARCHAR(50),
    reference_id INTEGER,
    note TEXT,
    user_id INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Auditoría
CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER,
    action VARCHAR(50),
    table_name VARCHAR(100),
    record_id INTEGER,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Configuraciones y Empresa
CREATE TABLE IF NOT EXISTS settings (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT,
    category VARCHAR(100) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Métodos de Pago Habilitados
CREATE TABLE IF NOT EXISTS payment_methods (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL, 
    code VARCHAR(100) UNIQUE NOT NULL,
    currency VARCHAR(10) DEFAULT 'VES',
    applies_igtf BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE
);

-- ==========================================
-- DATOS POR DEFECTO PARA INICIAR EL SISTEMA
-- ==========================================

-- 1. Insertar el Administrador por Default (admin / admin123)
INSERT INTO users (username, full_name, password, role, status) 
VALUES ('admin', 'Administrador Principal', '$2y$10$uNsBBWuU8WbvVEWXUFhw4uhzFxChRa937Mg/HuLlrmzFIIkrgQIPK', 'admin', 1) 
ON CONFLICT DO NOTHING;

-- 2. Métodos de Pago Base
INSERT INTO payment_methods (name, code, currency, applies_igtf, is_active) VALUES 
('USD Efectivo', 'usd_cash', 'USD', TRUE, TRUE),
('BS Efectivo', 'bs_cash', 'VES', FALSE, TRUE),
('BS Pago Móvil', 'bs_pm', 'VES', FALSE, TRUE),
('BS Punto Venta', 'bs_pos', 'VES', FALSE, TRUE),
('EUR Efectivo', 'eur_cash', 'EUR', TRUE, TRUE),
('Zelle', 'zelle', 'USD', TRUE, TRUE)
ON CONFLICT DO NOTHING;

-- 3. Tasas de Cambio y Variables Base
INSERT INTO settings (key, value, category) VALUES 
('bcv_rate', '622.21', 'rates'),
('parallel_rate', '0', 'rates'),
('cop_rate', '0', 'rates'),
('tax_iva', '16', 'fiscal'),
('tax_igtf', '3', 'fiscal'),
('calc_method', 'fiscal', 'fiscal'),
('iva_method', 'included', 'fiscal'),
('business_name', 'TuInventarioApp', 'company'),
('business_logo', '', 'company')
ON CONFLICT DO NOTHING;
