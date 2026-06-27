<?php
class DashboardController extends Controller {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'auth');
            exit;
        }

        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // 1. Productos Registrados
        $stmtProd = $db->query("SELECT COUNT(*) as count, 
            SUM(COALESCE(unit_cost, 0) * stock) as inv_value,
            SUM((price - COALESCE(unit_cost, 0)) * stock) as est_profit
            FROM products");
        $prodData = $stmtProd->fetch(PDO::FETCH_ASSOC);
        
        $activeProducts = $prodData['count'] ?: 0;
        $inventoryValue = $prodData['inv_value'] ?: 0;
        $estimatedProfit = $prodData['est_profit'] ?: 0;
        
        // 2. Ventas de hoy
        $today = date('Y-m-d');
        $stmtSales = $db->prepare("SELECT SUM(total) FROM sales WHERE date(created_at) = ?");
        $stmtSales->execute([$today]);
        $todaySales = $stmtSales->fetchColumn() ?: 0;
        
        // 3. Alertas de Stock
        $stmtStock = $db->query("SELECT COUNT(*) FROM products WHERE stock <= min_stock");
        $lowStock = $stmtStock->fetchColumn();

        // 4. Datos para gráfico de ventas de los últimos 7 días
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $st = $db->prepare("SELECT SUM(total) FROM sales WHERE date(created_at) = ?");
            $st->execute([$d]);
            $chartData[] = [
                'day' => date('d/m', strtotime("-$i days")),
                'sales' => $st->fetchColumn() ?: 0
            ];
        }
        
        $this->view('modules/dashboard/views/index', [
            'active_products' => $activeProducts,
            'inventory_value' => $inventoryValue,
            'estimated_profit' => $estimatedProfit,
            'today_sales' => $todaySales,
            'low_stock' => $lowStock,
            'chart_data' => $chartData
        ]);
    }

    public function backup() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            die("Acceso reservado a administradores.");
        }

        $dbFile = DB_PATH;
        if (file_exists($dbFile)) {
            $filename = 'TuInventario_Backup_' . date('Y_m_d_His') . '.db';
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Content-Length: ' . filesize($dbFile));
            readfile($dbFile);
            exit;
        } else {
            die("No se encontró la base de datos.");
        }
    }

    public function activity() {
        if (!isset($_SESSION['user_id'])) exit;
        
        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        // Obtenemos las últimas 10 ventas
        $stmt = $db->query("SELECT s.*, u.username as user FROM sales s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 10");
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($sales)) {
            echo '<div class="text-center text-gray-400 dark:text-gray-500 text-sm mt-10"><i class="fas fa-receipt text-4xl mb-3 block opacity-50"></i> No hay actividad reciente.</div>';
            return;
        }

        foreach ($sales as $sale) {
            $timeago = date('H:i', strtotime($sale['created_at']));
            $user = htmlspecialchars($sale['user'] ?? 'Sistema');
            
            // Buscar los items de esta venta
            $stmtItems = $db->prepare("SELECT si.quantity, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
            $stmtItems->execute([$sale['id']]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            
            $itemsText = [];
            foreach($items as $item) {
                $itemsText[] = $item['quantity'] . " " . htmlspecialchars($item['name']);
            }
            $productsSold = implode(", ", $itemsText);
            $saleTotal = number_format($sale['total'], 2);

            echo "
            <div class='flex items-start gap-4 pb-4 border-b border-gray-100 dark:border-gray-800/50 last:border-0 last:pb-0 group'>
                <div class='w-10 h-10 rounded-xl bg-gradient-to-br from-brand-50 to-brand-100 dark:from-brand-900/40 dark:to-brand-800/20 flex flex-col items-center justify-center flex-shrink-0 border border-brand-200/50 dark:border-brand-700/30 group-hover:scale-110 transition-transform'>
                    <i class='fas fa-tag text-brand-600 dark:text-brand-400 text-sm'></i>
                </div>
                <div class='flex-1 min-w-0'>
                    <div class='flex justify-between items-start mb-1'>
                        <p class='text-sm text-gray-800 dark:text-gray-200'>
                            <span class='font-bold bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-xs mr-1 text-gray-600 dark:text-gray-300'>@$user</span>
                            registró una venta
                        </p>
                        <span class='text-xs font-bold text-gray-400 shrink-0 ml-2'>$timeago</span>
                    </div>
                    
                    <div class='mt-2 p-3 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-gray-700/50 shadow-sm'>
                        <p class='text-xs text-gray-600 dark:text-gray-400 leading-relaxed mb-2'>
                            <span class='font-bold text-gray-800 dark:text-gray-200'>Vendió:</span> $productsSold
                        </p>
                        <div class='flex justify-between items-center'>
                            <span class='text-[10px] font-bold text-gray-400 uppercase tracking-wider'>Ticket #{$sale['id']}</span>
                            <span class='font-black text-brand-600 dark:text-brand-400'>$$saleTotal</span>
                        </div>
                    </div>
                </div>
            </div>";
        }
    }
}
