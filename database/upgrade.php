<?php
require_once __DIR__ . '/../config/Database.php';
$db = Database::getInstance()->getConnection();
$db->exec('CREATE TABLE IF NOT EXISTS audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, action TEXT, table_name TEXT, record_id INTEGER, details TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);');
$db->exec('CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP);');
echo "Tablas creadas.";
