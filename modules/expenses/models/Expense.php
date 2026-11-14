<?php
require_once __DIR__ . '/../../../core/Model.php';

class Expense extends Model {
    protected $table = 'expenses';
    protected $fillable = ['user_id', 'category', 'description', 'amount', 'expense_date'];

    private function currentBusinessId() {
        $businessId = (int)($_SESSION['business_id'] ?? 0);
        return $businessId > 0 ? $businessId : null;
    }

    public function allWithUser() {
        $business_id = $this->currentBusinessId();
        if ($business_id === null) return [];

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
        $business_id = $this->currentBusinessId();
        if ($business_id === null) return ['data' => [], 'total' => 0];

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

    public function find($id) {
        $businessId = $this->currentBusinessId();
        if ($businessId === null) return false;

        $stmt = $this->db->prepare("
            SELECT e.*
            FROM expenses e
            JOIN users u ON u.id = e.user_id
            WHERE e.id = ? AND u.business_id = ?
        ");
        $stmt->execute([$id, $businessId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $businessId = $this->currentBusinessId();
        if ($businessId === null) return false;

        $allowedFields = ['category', 'description', 'amount', 'expense_date'];
        $data = array_intersect_key($data, array_flip($allowedFields));
        if (empty($data)) return false;

        $assignments = [];
        foreach (array_keys($data) as $field) {
            $assignments[] = "{$field} = :{$field}";
        }
        $data['id'] = $id;
        $data['business_id'] = $businessId;

        $stmt = $this->db->prepare("
            UPDATE expenses
            SET " . implode(', ', $assignments) . "
            WHERE id = :id
              AND user_id IN (SELECT id FROM users WHERE business_id = :business_id)
        ");
        $updated = $stmt->execute($data);
        if ($updated) {
            $this->logAudit('UPDATE', $id, array_diff_key($data, ['id' => true, 'business_id' => true]));
        }
        return $updated;
    }

    public function delete($id) {
        $businessId = $this->currentBusinessId();
        if ($businessId === null) return false;

        $stmt = $this->db->prepare("
            DELETE FROM expenses
            WHERE id = :id
              AND user_id IN (SELECT id FROM users WHERE business_id = :business_id)
        ");
        $deleted = $stmt->execute(['id' => $id, 'business_id' => $businessId]);
        if ($deleted) {
            $this->logAudit('DELETE', $id, []);
        }
        return $deleted;
    }
}
