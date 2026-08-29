<?php
require_once __DIR__ . '/../../../core/Model.php';

class Expense extends Model {
    protected $table = 'expenses';
    protected $fillable = ['user_id', 'category', 'description', 'amount', 'expense_date'];

    public function allWithUser() {
        $business_id = $_SESSION['business_id'] ?? 1;
        $stmt = $this->db->prepare("
            SELECT e.*, u.username as user_name 
            FROM {$this->table} e 
            JOIN users u ON e.user_id = u.id 
            WHERE u.business_id = ?
            ORDER BY e.expense_date DESC, e.id DESC
        ");
        $stmt->execute([$business_id]);
        return $stmt->fetchAll();
    }
    public function allWithUserPaginated($limit, $offset) {
        $business_id = $_SESSION['business_id'] ?? 1;

        $countStmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM {$this->table} e 
            JOIN users u ON e.user_id = u.id 
            WHERE u.business_id = ?
        ");
        $countStmt->execute([$business_id]);
        $total = $countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT e.*, u.username as user_name 
            FROM {$this->table} e 
            JOIN users u ON e.user_id = u.id 
            WHERE u.business_id = ?
            ORDER BY e.expense_date DESC, e.id DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ");
        $stmt->execute([$business_id]);
        
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total
        ];
    }
}
