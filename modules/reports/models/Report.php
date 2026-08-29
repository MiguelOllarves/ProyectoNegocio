<?php
require_once __DIR__ . '/../../../core/Model.php';

class Report extends Model {
    
    public function getKardex($productId = null) {
        $business_id = $_SESSION['business_id'] ?? 1;
        $sql = "SELECT k.*, p.name as product_name, p.sku, u.username as user_name 
                FROM kardex k 
                JOIN products p ON k.product_id = p.id 
                LEFT JOIN users u ON k.user_id = u.id 
                WHERE p.tenant_id = ?";
        $params = [$business_id];
        if ($productId) {
            $sql .= " AND k.product_id = ? ";
            $params[] = $productId;
        }
        $sql .= " ORDER BY k.created_at DESC, k.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getKardexCount($productId = null) {
        $business_id = $_SESSION['business_id'] ?? 1;
        $sql = "SELECT COUNT(*) 
                FROM kardex k 
                JOIN products p ON k.product_id = p.id 
                WHERE p.tenant_id = ?";
        $params = [$business_id];
        if ($productId) {
            $sql .= " AND k.product_id = ? ";
            $params[] = $productId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getKardexPaginated($productId = null, $limit = 5, $offset = 0) {
        $business_id = $_SESSION['business_id'] ?? 1;
        $sql = "SELECT k.*, p.name as product_name, p.sku, u.username as user_name 
                FROM kardex k 
                JOIN products p ON k.product_id = p.id 
                LEFT JOIN users u ON k.user_id = u.id 
                WHERE p.tenant_id = ?";
        $params = [$business_id];
        if ($productId) {
            $sql .= " AND k.product_id = ? ";
            $params[] = $productId;
        }
        $sql .= " ORDER BY k.created_at DESC, k.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getFinancialSummary($startDate, $endDate) {
        $business_id = $_SESSION['business_id'] ?? 1;
        
        $sqlSales = "
            SELECT 
                COALESCE(SUM(s.total), 0) as income,
                COALESCE(SUM(s.iva), 0) as taxes
            FROM sales s 
            JOIN users u ON s.user_id = u.id
            WHERE DATE(s.created_at) BETWEEN ? AND ? AND u.business_id = ?
        ";
        $stmtS = $this->db->prepare($sqlSales);
        $stmtS->execute([$startDate, $endDate, $business_id]);
        $salesData = $stmtS->fetch(PDO::FETCH_ASSOC);

        $sqlCost = "
            SELECT COALESCE(SUM(si.quantity * si.cost_at_sale), 0) as sales_cost
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            JOIN products p ON si.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ? AND p.tenant_id = ?
        ";
        $stmtCost = $this->db->prepare($sqlCost);
        $stmtCost->execute([$startDate, $endDate, $business_id]);
        $salesCost = $stmtCost->fetchColumn();

        $stmtInv = $this->db->prepare("SELECT COALESCE(SUM(stock * COALESCE(unit_cost, 0)), 0) FROM products WHERE tenant_id = ?");
        $stmtInv->execute([$business_id]);
        $inventoryValue = $stmtInv->fetchColumn();

        return [
            'income' => $salesData['income'],
            'taxes' => $salesData['taxes'],
            'sales_cost' => $salesCost,
            'profit' => $salesData['income'] - $salesData['taxes'] - $salesCost,
            'inventory_value' => $inventoryValue
        ];
    }
    
    public function getSalesDetail($startDate, $endDate) {
        $business_id = $_SESSION['business_id'] ?? 1;
        $aggFunc = (DB_DRIVER === 'pgsql') ? "STRING_AGG(si.quantity || ' ' || p.name, ' | ')" : "GROUP_CONCAT(si.quantity || ' ' || p.name, ' | ')";
        
        $sql = "
            SELECT 
                s.id, 
                s.created_at as date,
                s.total,
                s.iva,
                s.igtf,
                COALESCE(SUM(si.quantity * si.cost_at_sale), 0) as cost_calculated,
                $aggFunc as detail
            FROM sales s
            JOIN sale_items si ON s.id = si.sale_id
            JOIN products p ON si.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ? AND p.tenant_id = ?
            GROUP BY s.id
            ORDER BY s.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $business_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getSalesCount($startDate, $endDate) {
        $business_id = $_SESSION['business_id'] ?? 1;
        $sql = "
            SELECT COUNT(DISTINCT s.id) 
            FROM sales s
            JOIN sale_items si ON s.id = si.sale_id
            JOIN products p ON si.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ? AND p.tenant_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $business_id]);
        return $stmt->fetchColumn();
    }

    public function getSalesDetailPaginated($startDate, $endDate, $limit, $offset) {
        $business_id = $_SESSION['business_id'] ?? 1;
        $aggFunc = (DB_DRIVER === 'pgsql') ? "STRING_AGG(si.quantity || ' ' || p.name, ' | ')" : "GROUP_CONCAT(si.quantity || ' ' || p.name, ' | ')";
        
        $sql = "
            SELECT 
                s.id, 
                s.created_at as date,
                s.total,
                s.iva,
                s.igtf,
                COALESCE(SUM(si.quantity * si.cost_at_sale), 0) as cost_calculated,
                $aggFunc as detail
            FROM sales s
            JOIN sale_items si ON s.id = si.sale_id
            JOIN products p ON si.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ? AND p.tenant_id = ?
            GROUP BY s.id
            ORDER BY s.created_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate, $business_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAuditLogsCount() {
        $business_id = $_SESSION['business_id'] ?? 1;
        $sql = "
            SELECT COUNT(a.id) 
            FROM audit_logs a 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE u.business_id = ? OR a.user_id IS NULL
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$business_id]);
        return $stmt->fetchColumn();
    }

    public function getAuditLogsPaginated($limit, $offset) {
        $business_id = $_SESSION['business_id'] ?? 1;
        $sql = "
            SELECT a.*, u.full_name as user_name 
            FROM audit_logs a 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE u.business_id = ? OR a.user_id IS NULL
            ORDER BY a.created_at DESC 
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$business_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
