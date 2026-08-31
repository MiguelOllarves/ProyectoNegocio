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

-- (Demo Business and User creation removed)
