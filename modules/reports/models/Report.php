<?php
require_once __DIR__ . '/../../../core/Model.php';

class Report extends Model {
    
    public function getKardex($productId = null) {
        $sql = "SELECT k.*, p.name as product_name, p.sku, u.username as user_name 
                FROM kardex k 
                JOIN products p ON k.product_id = p.id 
                LEFT JOIN users u ON k.user_id = u.id ";
        $params = [];
        if ($productId) {
            $sql .= " WHERE k.product_id = ? ";
            $params[] = $productId;
        }
        $sql .= " ORDER BY k.created_at DESC, k.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getFinancialSummary($startDate, $endDate) {
        // Ingresos y Ganancias basandonos en las ventas del periodo
        // Para calcular la ganancia y costo histórico, necesitamos sumar el costo base de los productos vendidos 
        // Si no tenemos cost_at_sale (histórico), usamos el unit_cost actual de products como aproximación.
        $sqlSales = "
            SELECT 
                COALESCE(SUM(s.total), 0) as income,
                COALESCE(SUM(s.iva), 0) as taxes
            FROM sales s 
            WHERE DATE(s.created_at) BETWEEN ? AND ?
        ";
        $stmtS = $this->db->prepare($sqlSales);
        $stmtS->execute([$startDate, $endDate]);
        $salesData = $stmtS->fetch(PDO::FETCH_ASSOC);

        // Costo de las ventas del periodo
        $sqlCost = "
            SELECT COALESCE(SUM(si.quantity * COALESCE(p.unit_cost, 0)), 0) as sales_cost
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            JOIN products p ON si.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
        ";
        $stmtCost = $this->db->prepare($sqlCost);
        $stmtCost->execute([$startDate, $endDate]);
        $salesCost = $stmtCost->fetchColumn();

        // Inventario Total Actual
        $stmtInv = $this->db->query("SELECT COALESCE(SUM(stock * COALESCE(unit_cost, 0)), 0) FROM products");
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
        $aggFunc = (DB_DRIVER === 'pgsql') ? "STRING_AGG(si.quantity || ' ' || p.name, ' | ')" : "GROUP_CONCAT(si.quantity || ' ' || p.name, ' | ')";
        
        $sql = "
            SELECT 
                s.id, 
                s.created_at as date,
                s.total,
                s.iva,
                s.igtf,
                COALESCE(SUM(si.quantity * COALESCE(p.unit_cost, 0)), 0) as cost_calculated,
                $aggFunc as detail
            FROM sales s
            JOIN sale_items si ON s.id = si.sale_id
            JOIN products p ON si.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN ? AND ?
            GROUP BY s.id
            ORDER BY s.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
