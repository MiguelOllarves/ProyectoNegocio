<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            if (DB_DRIVER === 'pgsql') {
                $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            } else {
                // Default: SQLite
                $this->pdo = new PDO("sqlite:" . DB_PATH);
                $this->pdo->exec("PRAGMA foreign_keys = ON;");
                $this->pdo->exec("PRAGMA journal_mode = WAL;"); // Better concurrency
            }

            // Shared PDO attributes for both drivers
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // Auto-migración: garantizar que todas las tablas existan
            require_once __DIR__ . '/../database/Migration.php';
            Migration::ensureTablesExist($this->pdo);

        } catch (PDOException $e) {
            die("Error de conexión a la base de datos (" . DB_DRIVER . "): " . $e->getMessage());
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
