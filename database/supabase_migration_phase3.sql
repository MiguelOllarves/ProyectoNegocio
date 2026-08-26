-- ============================================================
-- Migración para Supabase (PostgreSQL) - Fase 3 (Motor de Unidades)
-- Ejecuta este script en el SQL Editor de Supabase
-- ============================================================

-- 1. Crear tabla de unidades de medida
CREATE TABLE IF NOT EXISTS units_of_measure (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    abbreviation VARCHAR(20) NOT NULL,
    base_type VARCHAR(20) NOT NULL, -- 'peso', 'volumen', 'unidad'
    base_unit_id INTEGER,
    conversion_to_base NUMERIC(15, 6) DEFAULT 1.0
);

-- 2. Insertar unidades base por defecto
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
ON CONFLICT (id) DO UPDATE SET 
    name = EXCLUDED.name,
    conversion_to_base = EXCLUDED.conversion_to_base;

-- Ajustar la secuencia de ID de Postgres
SELECT setval('units_of_measure_id_seq', (SELECT MAX(id) FROM units_of_measure));

-- 3. Añadir columnas a products
DO $$ 
BEGIN 
    BEGIN
        ALTER TABLE products ADD COLUMN measurement_type VARCHAR(20) DEFAULT 'unidad';
    EXCEPTION WHEN duplicate_column THEN END;
    BEGIN
        ALTER TABLE products ADD COLUMN base_unit_id INTEGER;
    EXCEPTION WHEN duplicate_column THEN END;
    BEGIN
        ALTER TABLE products ADD COLUMN purchase_unit_id INTEGER;
    EXCEPTION WHEN duplicate_column THEN END;
    BEGIN
        ALTER TABLE products ADD COLUMN content_per_purchase NUMERIC(15,2) DEFAULT 1.0;
    EXCEPTION WHEN duplicate_column THEN END;
    BEGIN
        ALTER TABLE products ADD COLUMN contained_unit_id INTEGER;
    EXCEPTION WHEN duplicate_column THEN END;
    BEGIN
        ALTER TABLE products ADD COLUMN sale_unit_id INTEGER;
    EXCEPTION WHEN duplicate_column THEN END;
END $$;

-- 4. Modificar tablas transaccionales (quantity -> NUMERIC)
ALTER TABLE sale_items ALTER COLUMN quantity TYPE NUMERIC USING quantity::NUMERIC;
ALTER TABLE purchase_items ALTER COLUMN quantity TYPE NUMERIC USING quantity::NUMERIC;
ALTER TABLE kardex ALTER COLUMN quantity TYPE NUMERIC USING quantity::NUMERIC;
ALTER TABLE kardex ALTER COLUMN stock_after TYPE NUMERIC USING stock_after::NUMERIC;

-- 5. Añadir columnas de trazabilidad
DO $$ 
BEGIN 
    BEGIN
        ALTER TABLE sale_items ADD COLUMN normalized_quantity NUMERIC(15,2) DEFAULT 0;
    EXCEPTION WHEN duplicate_column THEN END;
    BEGIN
        ALTER TABLE sale_items ADD COLUMN unit_id INTEGER;
    EXCEPTION WHEN duplicate_column THEN END;
    
    BEGIN
        ALTER TABLE purchase_items ADD COLUMN normalized_quantity NUMERIC(15,2) DEFAULT 0;
    EXCEPTION WHEN duplicate_column THEN END;
    BEGIN
        ALTER TABLE purchase_items ADD COLUMN unit_id INTEGER;
    EXCEPTION WHEN duplicate_column THEN END;
    
    BEGIN
        ALTER TABLE kardex ADD COLUMN normalized_quantity NUMERIC(15,2) DEFAULT 0;
    EXCEPTION WHEN duplicate_column THEN END;
    BEGIN
        ALTER TABLE kardex ADD COLUMN unit_id INTEGER;
    EXCEPTION WHEN duplicate_column THEN END;
END $$;
