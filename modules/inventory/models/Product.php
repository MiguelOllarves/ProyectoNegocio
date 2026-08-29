<?php
require_once __DIR__ . '/../../../core/Model.php';

class Product extends Model {
    protected $table = 'products';
    protected $tenantColumn = 'tenant_id';

    // Retrieve all products with their categories and brands
    public function allWithCategoriesAndBrands() {
        $sql = "SELECT p.id, p.tenant_id, p.category_id, p.brand_id, p.supplier_id, p.name, p.sku, p.barcode, 
                       p.cost_type, p.unit_cost, p.bulk_cost, p.units_per_bulk, p.currency, p.profit_margin, p.price, 
                       p.is_tax_exempt, p.stock, p.unit_of_measure, p.measurement_type, p.base_unit_id, p.purchase_unit_id, 
                       p.content_per_purchase, p.contained_unit_id, p.sale_unit_id, p.allow_fractional_sales, p.conversion_factor, 
                       p.min_stock, p.dynamic_attributes, p.is_dish, p.prep_time, p.created_at,
                       (CASE WHEN length(p.image) > 255 THEN 'base64' ELSE p.image END) as image,
                       c.name as category_name, b.name as brand_name, 
                       u.abbreviation as sale_unit_abbr, u.name as sale_unit_name, u.conversion_to_base as sale_unit_factor,
                       u2.abbreviation as base_unit_abbr, u2.name as base_unit_name
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN units_of_measure u ON p.sale_unit_id = u.id
                LEFT JOIN units_of_measure u2 ON p.base_unit_id = u2.id";
        
        $params = [];
        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $sql .= " WHERE p.{$this->tenantColumn} = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }
        $sql .= " ORDER BY p.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allWithCategoriesAndBrandsPaginated($limit, $offset) {
        $where = "";
        $params = [];
        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $where = " WHERE p.{$this->tenantColumn} = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }

        $countSql = "SELECT COUNT(*) FROM {$this->table} p $where";
        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($params);
        $total = $stmtCount->fetchColumn();

        $sql = "SELECT p.id, p.tenant_id, p.category_id, p.brand_id, p.supplier_id, p.name, p.sku, p.barcode, 
                       p.cost_type, p.unit_cost, p.bulk_cost, p.units_per_bulk, p.currency, p.profit_margin, p.price, 
                       p.is_tax_exempt, p.stock, p.unit_of_measure, p.measurement_type, p.base_unit_id, p.purchase_unit_id, 
                       p.content_per_purchase, p.contained_unit_id, p.sale_unit_id, p.allow_fractional_sales, p.conversion_factor, 
                       p.min_stock, p.dynamic_attributes, p.is_dish, p.prep_time, p.created_at,
                       (CASE WHEN length(p.image) > 255 THEN 'base64' ELSE p.image END) as image,
                       c.name as category_name, b.name as brand_name, 
                       u.abbreviation as sale_unit_abbr, u.name as sale_unit_name, u.conversion_to_base as sale_unit_factor,
                       u2.abbreviation as base_unit_abbr, u2.name as base_unit_name
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN units_of_measure u ON p.sale_unit_id = u.id
                LEFT JOIN units_of_measure u2 ON p.base_unit_id = u2.id
                $where
                ORDER BY p.name ASC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        return [
            'data' => $data,
            'total' => $total
        ];
    }

    public function all() {
        $sql = "SELECT id, tenant_id, category_id, brand_id, supplier_id, name, sku, barcode, cost_type, unit_cost, bulk_cost, units_per_bulk, currency, profit_margin, price, is_tax_exempt, stock, unit_of_measure, measurement_type, base_unit_id, purchase_unit_id, content_per_purchase, contained_unit_id, sale_unit_id, allow_fractional_sales, conversion_factor, min_stock, dynamic_attributes, is_dish, prep_time, created_at, (CASE WHEN length(image) > 255 THEN 'base64' ELSE image END) as image FROM {$this->table}";
        $params = [];
        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $sql .= " WHERE {$this->tenantColumn} = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find($id) {
        $sql = "SELECT id, tenant_id, category_id, brand_id, supplier_id, name, sku, barcode, cost_type, unit_cost, bulk_cost, units_per_bulk, currency, profit_margin, price, is_tax_exempt, stock, unit_of_measure, measurement_type, base_unit_id, purchase_unit_id, content_per_purchase, contained_unit_id, sale_unit_id, allow_fractional_sales, conversion_factor, min_stock, dynamic_attributes, is_dish, prep_time, created_at, (CASE WHEN length(image) > 255 THEN 'base64' ELSE image END) as image FROM {$this->table} WHERE id = :id";
        $params = ['id' => $id];
        if ($this->tenantColumn && isset($_SESSION['business_id'])) {
            $sql .= " AND {$this->tenantColumn} = :tenant_id";
            $params['tenant_id'] = $_SESSION['business_id'];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
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
            throw $e;
        }
    }
    public function getMeta($productId) {
        $sql = "SELECT meta_key, meta_value FROM product_meta WHERE product_id = :pid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pid' => $productId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['meta_key']] = $row['meta_value'];
        }
        return $result;
    }

    public function updateWithMeta($id, $data, $metaAttributes) {
        $this->db->beginTransaction();
        try {
            // Update product
            $this->update($id, $data);
            
            // Delete old meta
            $sqlDel = "DELETE FROM product_meta WHERE product_id = :pid";
            $stmtDel = $this->db->prepare($sqlDel);
            $stmtDel->execute(['pid' => $id]);
            
            // Insert new meta
            if (!empty($metaAttributes)) {
                $sqlMeta = "INSERT INTO product_meta (product_id, meta_key, meta_value) VALUES (:pid, :key, :val)";
                $stmtMeta = $this->db->prepare($sqlMeta);
                foreach ($metaAttributes as $key => $val) {
                    if (!empty($val)) {
                        $stmtMeta->execute([
                            'pid' => $id,
                            'key' => $key,
                            'val' => $val
                        ]);
                    }
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
