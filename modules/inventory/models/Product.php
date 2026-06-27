<?php
require_once __DIR__ . '/../../../core/Model.php';

class Product extends Model {
    protected $table = 'products';

    // Retrieve all products with their categories and brands
    public function allWithCategoriesAndBrands() {
        $sql = "SELECT p.*, c.name as category_name, b.name as brand_name
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id
                ORDER BY p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Save product with dynamic meta attributes
    public function createWithMeta($data, $metaAttributes) {
        $this->db->beginTransaction();
        try {
            // First save product
            $productId = $this->create($data);
            if (!$productId) throw new Exception("Error saving product");
            
            // Then save meta attributes (IMEI, Color, Talla, etc)
            if (!empty($metaAttributes)) {
                $sqlMeta = "INSERT INTO product_meta (product_id, meta_key, meta_value) VALUES (:pid, :key, :val)";
                $stmtMeta = $this->db->prepare($sqlMeta);
                
                foreach ($metaAttributes as $key => $val) {
                    if (!empty($val)) {
                        $stmtMeta->execute([
                            'pid' => $productId,
                            'key' => $key,
                            'val' => $val
                        ]);
                    }
                }
            }
            $this->db->commit();
            return $productId;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
