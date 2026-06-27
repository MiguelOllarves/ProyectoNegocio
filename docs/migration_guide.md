# Guía de Migración (SQLite -> PostgreSQL)

El sistema soporta soporte nativo multiplataforma (PDO) para SQLite (desarrollo/local) y PostgreSQL (producción/entornos escalables).

## Prerequisitos
- Servidor PostgreSQL instalado y configurado.
- Extensión `pdo_pgsql` habilitada en PHP (`php.ini`).

## Pasos para Migrar

1. **Crear base de datos en PostgreSQL.**
   ```sql
   CREATE DATABASE tu_inventario;
   ```

2. **Modificar configuración del ERP.**
   Abrir `config/config.php` y actualizar:
   ```php
   define('DB_DRIVER', 'pgsql');
   
   define('DB_HOST', 'localhost');
   define('DB_PORT', '5432');
   define('DB_NAME', 'tu_inventario');
   define('DB_USER', 'postgres');
   define('DB_PASS', 'tu_password');
   ```

3. **Ejecutar el Schema.**
   - Debes modificar levemente el esquema `database/schema.sql` (Las tablas `INTEGER PRIMARY KEY AUTOINCREMENT` por `SERIAL PRIMARY KEY`).
   - SQLite `DATETIME` puede ser reemplazado por `TIMESTAMP`.
   - SQLite `REAL` puede ser reemplazado por `NUMERIC(12,2)`.

4. **Verificar.**
   Refresca el navegador. El sistema detectará automáticamente el driver y las sentencias PDO generadas internamente se adaptarán sin afectar la lógica de los controladores y modelos.
