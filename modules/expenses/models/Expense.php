<?php
require_once __DIR__ . '/../../../core/Model.php';

class Expense extends Model {
    protected $table = 'expenses';
    protected $fillable = ['user_id', 'category', 'description', 'amount', 'expense_date'];

    public function allWithUser() {
        $stmt = $this->db->prepare("
            SELECT e.*, u.username as user_name 
            FROM {$this->table} e 
            LEFT JOIN users u ON e.user_id = u.id 
            ORDER BY e.expense_date DESC, e.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
