<?php
require_once __DIR__ . '/../config/Database.php';

class Model {
    protected $db;
    protected $table;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    protected function logAudit($action, $recordId, $details = []) {
        if ($this->table === 'audit_logs') return;
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $stmt = $this->db->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, details) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $action, $this->table, $recordId, json_encode($details, JSON_UNESCAPED_UNICODE)]);
        } catch (\Exception $e) { }
    }

    public function all() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $columns = implode(', ', array_keys($data));
        
        $placeholders = array_map(function($key) {
            return ":$key";
        }, array_keys($data));
        $placeholdersStr = implode(', ', $placeholders);
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholdersStr)";
        $stmt = $this->db->prepare($sql);
        
        // Uso estricto de Consultas Preparadas (Prepared Statements) para prevenir Inyección SQL
        if ($stmt->execute($data)) {
            $id = $this->db->lastInsertId();
            $this->logAudit('CREATE', $id, $data);
            return $id;
        }
        return false;
    }

    public function update($id, $data) {
        $fields = "";
        foreach ($data as $key => $value) {
            $fields .= "$key = :$key, ";
        }
        $fields = rtrim($fields, ', ');
        
        $data['id'] = $id; // Agregamos el ID a la data para que el prepared statement lo encuentre
        $sql = "UPDATE {$this->table} SET $fields WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        $res = $stmt->execute($data);
        if ($res) {
            $this->logAudit('UPDATE', $id, $data);
        }
        return $res;
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $res = $stmt->execute(['id' => $id]);
        if ($res) {
            $this->logAudit('DELETE', $id, []);
        }
        return $res;
    }
}
