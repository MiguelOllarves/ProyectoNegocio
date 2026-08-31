DO $$
DECLARE
    row record;
BEGIN
    FOR row IN SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
    LOOP
        EXECUTE 'TRUNCATE TABLE "' || row.table_name || '" RESTART IDENTITY CASCADE';
    END LOOP;
END;
$$;

-- Insertar SuperAdmin (No necesita negocio)
INSERT INTO users (business_id, username, full_name, password, role, status) 
VALUES (NULL, '182247576', 'Super Administrador', '$2y$10$Cp9ZbTwmvDgIMasoEqD5l.xsvpIkTaxE8hWzgcfRBTp2DZPImRsyi', 'super_admin', 1);

-- Insertar el Negocio Demo
INSERT INTO businesses (owner_name, business_name, rif, owner_phone, business_phone, document_id, email, category, slug) 
VALUES ('Demo', 'Negocio Demo', 'J-00000000-0', '0000', '0000', '00000000', 'demo@tuinventario.app', 'general', 'negocio-demo');

-- Insertar Usuario Demo vinculado dinámicamente al negocio creado arriba
INSERT INTO users (business_id, username, full_name, password, role, status) 
VALUES (
  (SELECT id FROM businesses WHERE document_id = '00000000' LIMIT 1), 
  '00000000', 'Usuario Demo', '$2y$10$4CIEIUHPYkNpXcgoy4zmAuK7R93Nk6WCqISbmADEZjWmNzoZROYL.', 'administrador', 1
);
