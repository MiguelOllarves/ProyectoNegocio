<?php
require_once __DIR__ . '/../../../core/Model.php';

class ProductPresentation extends Model {
    protected $table = 'product_presentations';
    // tenant_id no es estrictamente necesario porque está en cascada por product_id, pero se podría unir si se requiere
    
    /**
     * Obtiene todas las presentaciones de un producto.
     */
    public function getByProduct($productId) {
        $sql = "SELECT pp.*, u.name as unit_name, u.abbreviation as unit_abbr 
                FROM {$this->table} pp 
                LEFT JOIN units_of_measure u ON pp.unit_id = u.id 
                WHERE pp.product_id = :pid 
                ORDER BY pp.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pid' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Sincroniza (reemplaza) todas las presentaciones de un producto.
     */
    public function syncForProduct($productId, $presentationsData) {
        $this->db->beginTransaction();
        try {
            // Eliminar todas las presentaciones actuales de este producto
            $sqlDel = "DELETE FROM {$this->table} WHERE product_id = :pid";
            $stmtDel = $this->db->prepare($sqlDel);
            $stmtDel->execute(['pid' => $productId]);
            
            // Insertar de nuevo la lista que se está pasando
            if (!empty($presentationsData) && is_array($presentationsData)) {
                $sqlAdd = "INSERT INTO {$this->table} (product_id, name, quantity, unit_id) VALUES (:pid, :name, :qty, :uid)";
                $stmtAdd = $this->db->prepare($sqlAdd);
                
                foreach ($presentationsData as $pres) {
                    // Validar mínimos de data
                    if (!empty($pres['name']) && !empty($pres['quantity'])) {
                        $stmtAdd->execute([
                            'pid'  => $productId,
                            'name' => $pres['name'],
                            'qty'  => $pres['quantity'],
                            'uid'  => !empty($pres['unit_id']) ? $pres['unit_id'] : null
                        ]);
                    }
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[ProductPresentation] Sync Error: ' . $e->getMessage());
            return false;
        }
    }
}
