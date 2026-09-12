<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);

            // PDO attributes
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // EMULATE_PREPARES=true es OBLIGATORIO con Supabase pooler (PgBouncer):
            // PgBouncer en modo transacción no soporta prepared statements nativos del
            // servidor porque las conexiones se migran entre backends PostgreSQL.
            // Con emulación cliente, PDO escapa los parámetros localmente (seguro contra
            // inyección SQL) y evita el error "Invalid sql statement name".
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

            // PgBouncer necesita desactivar prepared statements persistentes a nivel driver
            if (defined('PDO::PGSQL_ATTR_DISABLE_PREPARES')) {
                try {
                    $this->pdo->setAttribute(\PDO::PGSQL_ATTR_DISABLE_PREPARES, true);
                } catch (\PDOException $e) {
                    // Opcional: no rompe la conexión si el driver no lo soporta
                }
            }

            // Auto-migración: garantizar que todas las tablas existan
            require_once __DIR__ . '/../database/Migration.php';
            Migration::ensureTablesExist($this->pdo);

        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Error de conexión a la base de datos. Contacte al administrador.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    // --- Transaction Wrappers ---
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollBack();
    }

    public function getDriver() {
        return DB_DRIVER;
    }
}
