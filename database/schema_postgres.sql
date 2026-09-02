-- ============================================================
-- TuInventarioApp v4.0 - Schema PostgreSQL Optimizado
-- Solo CREATE IF NOT EXISTS - Seguro para re-ejecución
-- ============================================================

-- 1. Negocios / Inquilinos
CREATE TABLE IF NOT EXISTS businesses (
    id SERIAL PRIMARY KEY,
    owner_name VARCHAR(255) NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    rif VARCHAR(50),
    owner_phone VARCHAR(50),
    business_phone VARCHAR(50),
    document_id VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    category VARCHAR(100) NOT NULL,
    slug VARCHAR(255),
    subscription_status VARCHAR(50) DEFAULT 'trial',
    trial_ends_at TIMESTAMP,
    logo_base64 TEXT,
    ticket_header TEXT,
    ticket_footer TEXT,
    menu_file_base64 TEXT,
    menu_file_type TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 1.1 Planes de Suscripción
CREATE TABLE IF NOT EXISTS plans (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    duration_days INTEGER NOT NULL,
    features_json TEXT
);

-- 1.2 Registro de Pagos
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    plan_id INTEGER REFERENCES plans(id),
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    reference_number VARCHAR(100) NOT NULL,
    proof_image TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Usuarios y Roles
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    business_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    username VARCHAR(255) NOT NULL UNIQUE,
    full_name VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'vendedor' CHECK (role IN ('super_admin', 'administrador', 'empleado', 'vendedor')),
    status SMALLINT DEFAULT 1,
    permissions_json JSONB,
    active_session_id VARCHAR(255),
    last_ip VARCHAR(45),
    last_device VARCHAR(255),
    last_location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Categorías
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    UNIQUE(tenant_id, name)
);

-- 4. Marcas
CREATE TABLE IF NOT EXISTS brands (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    UNIQUE(tenant_id, name)
);

-- 5. Proveedores
CREATE TABLE IF NOT EXISTS suppliers (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    contact_name VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Clientes
CREATE TABLE IF NOT EXISTS clients (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    document VARCHAR(50),
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    extra_phones TEXT,
    workplace TEXT,
    workplace_component TEXT,
    workplace_detail TEXT,
    workplace_address TEXT,
    monthly_income NUMERIC(15,2),
    ip_address TEXT,
    user_agent TEXT,
    gps_location TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Configuración de Tienda Pública
CREATE TABLE IF NOT EXISTS store_config (
    id SERIAL PRIMARY KEY,
    business_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    hero_title VARCHAR(255),
    hero_subtitle VARCHAR(255),
    primary_color VARCHAR(50) DEFAULT '#10b981',
    logo_url TEXT,
    whatsapp VARCHAR(50),
    instagram VARCHAR(100),
    facebook VARCHAR(255),
    tiktok VARCHAR(255),
    twitter VARCHAR(255),
    store_name TEXT,
    background_image TEXT,
    show_prices INTEGER DEFAULT 1,
    is_published INTEGER DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7.5 Unidades de Medida
CREATE TABLE IF NOT EXISTS units_of_measure (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    abbreviation VARCHAR(20) NOT NULL,
    base_type VARCHAR(20) NOT NULL,
    base_unit_id INTEGER,
    conversion_to_base NUMERIC(15, 6) DEFAULT 1.0
);

-- 8. Productos (Inventario General)
CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    brand_id INTEGER REFERENCES brands(id) ON DELETE SET NULL,
    supplier_id INTEGER REFERENCES suppliers(id) ON DELETE SET NULL,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100),
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
    measurement_type VARCHAR(20) DEFAULT 'unidad',
    base_unit_id INTEGER,
    purchase_unit_id INTEGER,
    content_per_purchase NUMERIC(15,2) DEFAULT 1.0,
    contained_unit_id INTEGER,
    sale_unit_id INTEGER,
    allow_fractional_sales BOOLEAN DEFAULT FALSE,
    conversion_factor NUMERIC(15,2) DEFAULT 1.0,
    min_stock NUMERIC(15,2) DEFAULT 5,
    image TEXT,
    dynamic_attributes TEXT,
    is_dish BOOLEAN DEFAULT FALSE,
    prep_time INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS product_meta (
    id SERIAL PRIMARY KEY,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    meta_key VARCHAR(100) NOT NULL,
    meta_value TEXT,
    UNIQUE(product_id, meta_key)
);

-- 8.5 Presentaciones Adicionales
CREATE TABLE IF NOT EXISTS product_presentations (
    id SERIAL PRIMARY KEY,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    quantity NUMERIC(15,2) NOT NULL DEFAULT 1.0,
    unit_id INTEGER REFERENCES units_of_measure(id) ON DELETE RESTRICT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- 8.6 Insumos de Cocina (Inventario Separado para Gastronomía)
-- ==========================================
CREATE TABLE IF NOT EXISTS kitchen_ingredients (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    unit_id INTEGER REFERENCES units_of_measure(id),
    cost_per_unit NUMERIC(15,4) DEFAULT 0,
    stock NUMERIC(15,4) DEFAULT 0,
    min_stock NUMERIC(15,4) DEFAULT 0,
    supplier_id INTEGER REFERENCES suppliers(id) ON DELETE SET NULL,
    image TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8.7 Recetas de Platos (Apuntan a products)
CREATE TABLE IF NOT EXISTS recipe_items (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    dish_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    ingredient_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    quantity NUMERIC(15,4) NOT NULL,
    unit_id INTEGER REFERENCES units_of_measure(id),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 9. Ventas
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

-- 10. Detalle de Ventas
CREATE TABLE IF NOT EXISTS sale_items (
    id SERIAL PRIMARY KEY,
    sale_id INTEGER REFERENCES sales(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE RESTRICT,
    quantity NUMERIC(15,2) NOT NULL,
    normalized_quantity NUMERIC(15,2) DEFAULT 0,
    unit_id INTEGER,
    price_at_sale NUMERIC(15,2) NOT NULL,
    cost_at_sale NUMERIC(15,2) DEFAULT 0
);

-- 11. Pagos Mixtos de Ventas
CREATE TABLE IF NOT EXISTS ventas_pagos (
    id SERIAL PRIMARY KEY,
    venta_id INTEGER REFERENCES sales(id) ON DELETE CASCADE,
    metodo_pago VARCHAR(50),
    monto_divisa NUMERIC(15,2) DEFAULT 0,
    monto_bs NUMERIC(15,2) DEFAULT 0,
    tasa_aplicada NUMERIC(15,2)
);

-- 12. Compras
CREATE TABLE IF NOT EXISTS purchases (
    id SERIAL PRIMARY KEY,
    supplier_id INTEGER REFERENCES suppliers(id) ON DELETE SET NULL,
    user_id INTEGER REFERENCES users(id),
    total NUMERIC(15,2) NOT NULL DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 13. Detalle de Compras
CREATE TABLE IF NOT EXISTS purchase_items (
    id SERIAL PRIMARY KEY,
    purchase_id INTEGER REFERENCES purchases(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE RESTRICT,
    presentation_id INTEGER REFERENCES product_presentations(id) ON DELETE SET NULL,
    quantity NUMERIC(15,2) NOT NULL,
    normalized_quantity NUMERIC(15,2) DEFAULT 0,
    unit_id INTEGER,
    unit_type VARCHAR(50) DEFAULT 'unidad',
    cost_per_unit NUMERIC(15,2) NOT NULL
);

-- 14. Gastos / Egresos
CREATE TABLE IF NOT EXISTS expenses (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    category VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    amount NUMERIC(15,2) NOT NULL,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 15. Arqueo de Caja
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

-- 16. Kardex
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

-- 17. Auditoría
CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    business_id INTEGER REFERENCES businesses(id) ON DELETE SET NULL,
    action VARCHAR(255) NOT NULL,
    target VARCHAR(255),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 18. Configuraciones
CREATE TABLE IF NOT EXISTS settings (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT,
    category VARCHAR(100) DEFAULT 'general',
    tenant_id INTEGER,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 19. Métodos de Pago
CREATE TABLE IF NOT EXISTS payment_methods (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(100) NOT NULL,
    currency VARCHAR(10) DEFAULT 'VES',
    applies_igtf BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE
);

-- 20. Créditos / Fiados
CREATE TABLE IF NOT EXISTS credits (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    client_id INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    sale_id INTEGER REFERENCES sales(id) ON DELETE SET NULL,
    total_amount NUMERIC(15,2) NOT NULL,
    paid_amount NUMERIC(15,2) DEFAULT 0,
    remaining_amount NUMERIC(15,2) NOT NULL,
    due_date DATE,
    status VARCHAR(50) DEFAULT 'activo' CHECK(status IN ('activo','pagado','atrasado','cancelado')),
    notes TEXT,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 21. Pagos de Crédito
CREATE TABLE IF NOT EXISTS credit_payments (
    id SERIAL PRIMARY KEY,
    credit_id INTEGER NOT NULL REFERENCES credits(id) ON DELETE CASCADE,
    amount NUMERIC(15,2) NOT NULL,
    payment_method VARCHAR(50),
    reference VARCHAR(255),
    status VARCHAR(50) DEFAULT 'pendiente' CHECK(status IN ('pendiente','aprobado','rechazado')),
    approved_by INTEGER REFERENCES users(id),
    approved_at TIMESTAMP,
    notes TEXT,
    reported_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 22. Notificaciones
CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    target_role VARCHAR(50),
    target_user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_type VARCHAR(100),
    reference_id INTEGER,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 23. Sesiones (para Vercel serverless)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    data TEXT,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 24. Pedidos de Tienda Web
CREATE TABLE IF NOT EXISTS store_orders (
    id SERIAL PRIMARY KEY,
    tenant_id INTEGER REFERENCES businesses(id) ON DELETE CASCADE,
    customer_name VARCHAR(255),
    customer_phone VARCHAR(50),
    customer_email VARCHAR(255),
    customer_address TEXT,
    items_json TEXT,
    total_usd NUMERIC(15,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pendiente',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 25. Visitas al Sitio
CREATE TABLE IF NOT EXISTS site_visits (
    id SERIAL PRIMARY KEY,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 26. Seguridad: IPs Baneadas
CREATE TABLE IF NOT EXISTS banned_ips (
    id SERIAL PRIMARY KEY,
    ip_address VARCHAR(45) UNIQUE,
    reason TEXT,
    banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 27. Seguridad: Rate Limiting
CREATE TABLE IF NOT EXISTS rate_limits (
    id SERIAL PRIMARY KEY,
    ip_address VARCHAR(45),
    action VARCHAR(50),
    attempts INTEGER DEFAULT 0,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(ip_address, action)
);

-- 28. Historial de Sesiones de Login
CREATE TABLE IF NOT EXISTS login_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_type VARCHAR(20),
    os_name VARCHAR(50),
    browser_name VARCHAR(50),
    location VARCHAR(255),
    fingerprint VARCHAR(255),
    logged_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 29. QR Menú Gratis
CREATE TABLE IF NOT EXISTS free_qr_menus (
    slug TEXT PRIMARY KEY,
    edit_code TEXT,
    menu_base64 TEXT,
    menu_type TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================
-- ÍNDICES PARA RENDIMIENTO
-- ==========================================
CREATE INDEX IF NOT EXISTS idx_created_at_sales ON sales(created_at);
CREATE INDEX IF NOT EXISTS idx_tenant_id_products ON products(tenant_id);
CREATE INDEX IF NOT EXISTS idx_kitchen_tenant ON kitchen_ingredients(tenant_id);
CREATE INDEX IF NOT EXISTS idx_recipe_dish ON recipe_items(dish_id);

-- ==========================================
-- DATOS POR DEFECTO
-- ==========================================

-- A) Negocio base
INSERT INTO businesses (id, owner_name, business_name, document_id, email, category, slug, subscription_status)
VALUES (1, 'Usuario Demo', 'Negocio Demo', '00000000', 'demo@sistema.local', 'general', 'demo', 'active')
ON CONFLICT DO NOTHING;

-- B) Administrador Demo
INSERT INTO users (business_id, username, full_name, password, role, status)
VALUES (1, '00000000', 'Administrador Demo', '$2y$10$SaSgH8hC.HnRdqMiiejSjuU4PD3NdwI2WZhKDkEJ7Yg/pigOpX7kG', 'administrador', 1)
ON CONFLICT DO NOTHING;

-- C) Super Admin Global
INSERT INTO users (business_id, username, full_name, password, role, status)
VALUES (NULL, '182247576', 'Super Administrador', '$2y$10$FdMFxslXhRhiK2iZ2qb64e3o7kiK1dfJOEOkp6RI2z2z3fqN8woP6', 'super_admin', 1)
ON CONFLICT DO NOTHING;

-- D) Métodos de Pago Base
INSERT INTO payment_methods (name, code, currency, applies_igtf, is_active) VALUES
('USD Efectivo', 'usd_cash', 'USD', TRUE, TRUE),
('BS Efectivo', 'bs_cash', 'VES', FALSE, TRUE),
('BS Pago Móvil', 'bs_pm', 'VES', FALSE, TRUE),
('BS Punto Venta', 'bs_pos', 'VES', FALSE, TRUE),
('EUR Efectivo', 'eur_cash', 'EUR', TRUE, TRUE),
('Zelle', 'zelle', 'USD', TRUE, TRUE)
ON CONFLICT DO NOTHING;

-- E) Configuraciones Base (BCV actualizado a 791.32)
INSERT INTO settings (key, value, category) VALUES
('bcv_rate', '791.32', 'rates'),
('parallel_rate', '0', 'rates'),
('cop_rate', '0', 'rates'),
('tax_iva', '16', 'fiscal'),
('tax_igtf', '3', 'fiscal'),
('calc_method', 'fiscal', 'fiscal'),
('iva_method', 'included', 'fiscal'),
('business_name', 'TuInventarioApp', 'company'),
('business_logo', '', 'company')
ON CONFLICT DO NOTHING;

-- F) Unidades de Medida
INSERT INTO units_of_measure (id, name, abbreviation, base_type, base_unit_id, conversion_to_base) VALUES
(1, 'Gramo', 'g', 'peso', 1, 1.0),
(2, 'Mililitro', 'ml', 'volumen', 2, 1.0),
(3, 'Unidad', 'und', 'unidad', 3, 1.0),
(4, 'Kilogramo', 'kg', 'peso', 1, 1000.0),
(8, 'Miligramo', 'mg', 'peso', 1, 0.001),
(9, 'Litro', 'L', 'volumen', 2, 1000.0),
(10, 'Caja 12L', 'cj12L', 'volumen', 2, 12000.0),
(11, 'Galón', 'gal', 'volumen', 2, 3785.41),
(12, 'Caja', 'cj', 'unidad', 3, 1.0),
(13, 'Bulto', 'bulto', 'unidad', 3, 1.0),
(14, 'Paquete', 'pqte', 'unidad', 3, 1.0)
ON CONFLICT DO NOTHING;

SELECT setval('units_of_measure_id_seq', (SELECT COALESCE(MAX(id), 1) FROM units_of_measure));
-- ==========================================
-- 30. Notificaciones Push
-- ==========================================
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(50),
    endpoint TEXT UNIQUE NOT NULL,
    p256dh TEXT,
    auth TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);