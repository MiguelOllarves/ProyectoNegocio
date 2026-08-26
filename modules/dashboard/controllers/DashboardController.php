<?php
class DashboardController extends Controller {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL);
            exit;
        }

        require_once __DIR__ . '/../../../config/Database.php';
        $db = Database::getInstance()->getConnection();
        
        $business_id = $_SESSION['business_id'] ?? 1;

        // 1. Productos Registrados y Valor del Inventario
        $stmtProd = $db->prepare("SELECT id, stock, price, unit_cost, conversion_factor as content_per_purchase, purchase_unit_id as contained_unit_id, sale_unit_id FROM products WHERE tenant_id = ? AND stock > 0");
        $stmtProd->execute([$business_id]);
        $products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
        
        require_once __DIR__ . '/../../../core/UnitConversionService.php';
        $units = UnitConversionService::getAllUnits();
        $unitMap = [];
        foreach($units as $u) {
            $unitMap[$u['id']] = $u['conversion_to_base'];
        }

        $activeProducts = count($products);
        $inventoryValue = 0;
        $estimatedProfit = 0;

        foreach ($products as $p) {
            // Calculate Cost per Base Unit
            $containedConv = $unitMap[$p['contained_unit_id']] ?? 1;
            $contentInBase = max((float)$p['content_per_purchase'], 0.0001) * $containedConv;
            $costPerBase = (float)$p['unit_cost'] / $contentInBase;
            
            $invVal = (float)$p['stock'] * $costPerBase;
            $inventoryValue += $invVal;
            
            // Calculate Price per Base Unit
            $saleConv = $unitMap[$p['sale_unit_id']] ?? 1;
            $pricePerBase = (float)$p['price'] / $saleConv;
            $saleVal = (float)$p['stock'] * $pricePerBase;
            
            $estimatedProfit += ($saleVal - $invVal);
        }
        
        // 2. Ventas de hoy y Gráfico (Unificando POS y Tienda Web)
        $stmtSales = $db->prepare("SELECT total, created_at FROM sales WHERE user_id IN (SELECT id FROM users WHERE business_id = ?) ORDER BY id DESC LIMIT 500");
        $stmtSales->execute([$business_id]);
        $recentSales = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

        $stmtOrders = $db->prepare("SELECT total_usd as total, created_at FROM store_orders WHERE tenant_id = ? AND status = 'despachado' ORDER BY id DESC LIMIT 500");
        $stmtOrders->execute([$business_id]);
        $recentOrders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

        $allTransactions = array_merge($recentSales, $recentOrders);

        $todaySales = 0;
        $currentDate = date('Y-m-d', time() - (4 * 3600)); // Ajuste VET
        if (!empty($allTransactions)) {
            foreach ($allTransactions as $t) {
                $localTime = strtotime($t['created_at']) - (4 * 3600); // Ajuste VET
                if (date('Y-m-d', $localTime) === $currentDate) {
                    $todaySales += $t['total'];
                }
            }
        }

        // 3. Alertas de Stock
        $stmtStock = $db->prepare("SELECT COUNT(*) FROM products WHERE stock <= min_stock AND tenant_id = ?");
        $stmtStock->execute([$business_id]);
        $lowStock = $stmtStock->fetchColumn() ?: 0;

        // 4. Datos para gráfico de ventas (últimos 7 días)
        $chartDataMap = [];
        $currentLocalTime = time() - (4 * 3600); // Ajuste VET
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', $currentLocalTime - ($i * 86400));
            $chartDataMap[$d] = 0;
        }

        foreach ($allTransactions as $t) {
            $localTime = strtotime($t['created_at']) - (4 * 3600); // Ajuste VET para agrupar midnights locales
            $sDate = date('Y-m-d', $localTime);
            if (isset($chartDataMap[$sDate])) {
                $chartDataMap[$sDate] += $t['total'];
            }
        }

        $chartData = [];
        foreach ($chartDataMap as $date => $total) {
            $chartData[] = [
                'day' => date('d/m', strtotime($date)),
                'sales' => $total
            ];
        }
        
        // 5. Subscription & Trial Info
        $stmtBiz = $db->prepare("SELECT subscription_status, trial_ends_at FROM businesses WHERE id = ?");
        $stmtBiz->execute([$business_id]);
        $bizInfo = $stmtBiz->fetch(PDO::FETCH_ASSOC);
        
        $this->view('modules/dashboard/views/index', [
            'active_products' => $activeProducts,
            'inventory_value' => $inventoryValue,
            'estimated_profit' => $estimatedProfit,
            'today_sales' => $todaySales,
            'low_stock' => $lowStock,
            'chart_data' => $chartData,
            'subscription_status' => $bizInfo['subscription_status'] ?? 'trial',
            'trial_ends_at' => $bizInfo['trial_ends_at'] ?? '2099-12-31'
        ]);
    }

    public function backup() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrador') {
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
        
        $business_id = $_SESSION['business_id'] ?? 1;

        $activities = [];

        // 1. Obtenemos las últimas 15 ventas del POS
        $stmtSales = $db->prepare("SELECT s.*, u.username as user FROM sales s JOIN users u ON s.user_id = u.id WHERE u.business_id = ? ORDER BY s.id DESC LIMIT 15");
        $stmtSales->execute([$business_id]);
        $sales = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

        $saleIds = array_column($sales, 'id');
        $itemsBySale = [];
        if (!empty($saleIds)) {
            $placeholders = implode(',', array_fill(0, count($saleIds), '?'));
            $stmtItems = $db->prepare("SELECT si.sale_id, si.quantity, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id IN ($placeholders)");
            $stmtItems->execute($saleIds);
            $allItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allItems as $item) {
                $itemsBySale[$item['sale_id']][] = $item;
            }
        }

        foreach ($sales as $sale) {
            $items = $itemsBySale[$sale['id']] ?? [];
            $itemsText = [];
            foreach($items as $item) { $itemsText[] = $item['quantity'] . " " . htmlspecialchars($item['name']); }
            
            $activities[] = [
                'type' => 'pos_sale',
                'id' => $sale['id'],
                'user' => $sale['user'],
                'total' => $sale['total'],
                'desc' => implode(", ", $itemsText),
                'created_at' => $sale['created_at'],
                'timestamp' => strtotime($sale['created_at'])
            ];
        }

        // 2. Obtenemos los últimos 15 pedidos de la Tienda Web
        $stmtOrders = $db->prepare("SELECT * FROM store_orders WHERE tenant_id = ? ORDER BY id DESC LIMIT 15");
        $stmtOrders->execute([$business_id]);
        $orders = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as $order) {
            $itemsArr = json_decode($order['items_json'] ?? '[]', true);
            $itemsText = [];
            if (is_array($itemsArr)) {
                foreach ($itemsArr as $item) {
                    $qty = isset($item['qty']) ? $item['qty'] : 1;
                    $name = isset($item['name']) ? htmlspecialchars($item['name']) : '';
                    $itemsText[] = $qty . ' ' . $name;
                }
            }
            
            $activities[] = [
                'type' => 'web_order',
                'id' => $order['id'],
                'user' => $order['customer_name'] ?: 'Cliente Anónimo',
                'total' => $order['total_usd'],
                'desc' => implode(", ", $itemsText),
                'status' => $order['status'],
                'created_at' => $order['created_at'],
                'timestamp' => strtotime($order['created_at'])
            ];
        }

        // 3. Ordenar por fecha descendente
        usort($activities, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        // Limitar a las 15 más recientes combinadas
        $activities = array_slice($activities, 0, 15);

        if (empty($activities)) {
            echo '<div class="text-center text-gray-400 dark:text-gray-500 text-sm mt-10"><i class="fas fa-receipt text-4xl mb-3 block opacity-50"></i> No hay actividad reciente.</div>';
            return;
        }

        foreach ($activities as $act) {
            $localTime = $act['timestamp'] - (4 * 3600); // Ajuste manual VET (Aprox)
            
            // Mostrar hora si fue hoy, o fecha si fue otro día
            if(date('Y-m-d', $localTime) === date('Y-m-d')) {
                $timeago = date('H:i', $localTime);
            } else {
                $timeago = date('d/m', $localTime) . ' a las ' . date('H:i', $localTime);
            }
            
            $total = number_format($act['total'], 2);

            if ($act['type'] === 'pos_sale') {
                $user = htmlspecialchars($act['user'] ?? 'Sistema');
                ?>
                <div class='flex items-start gap-4 pb-4 border-b border-gray-100 dark:border-gray-800/50 last:border-0 last:pb-0 group'>
                    <div class='w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/40 dark:to-emerald-800/20 flex flex-col items-center justify-center flex-shrink-0 border border-emerald-200/50 dark:border-emerald-700/30 group-hover:scale-110 transition-transform'>
                        <i class='fas fa-tag text-emerald-600 dark:text-emerald-400 text-sm'></i>
                    </div>
                    <div class='flex-1 min-w-0'>
                        <div class='flex justify-between items-start mb-1'>
                            <p class='text-sm text-gray-800 dark:text-gray-200'>
                                <span class='font-bold bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-xs mr-1 text-gray-600 dark:text-gray-300'>@<?= $user ?></span>
                                registró una venta POS
                            </p>
                            <span class='text-[10px] font-bold text-gray-400 shrink-0 ml-2'><?= $timeago ?></span>
                        </div>
                        <div class='mt-2 p-3 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-gray-700/50 shadow-sm'>
                            <p class='text-xs text-gray-600 dark:text-gray-400 leading-relaxed mb-2'>
                                <span class='font-bold text-gray-800 dark:text-gray-200'>Vendió:</span> <?= htmlspecialchars($act['desc']) ?>
                            </p>
                            <div class='flex justify-between items-center'>
                                <span class='text-[10px] font-bold text-gray-400 uppercase tracking-wider'>Ticket #<?= $act['id'] ?></span>
                                <span class='font-black text-emerald-600 dark:text-emerald-400'>$<?= $total ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            } else if ($act['type'] === 'web_order') {
                $customer = htmlspecialchars($act['user']);
                $badgeColor = $act['status'] === 'pendiente' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                ?>
                <div class='flex items-start gap-4 pb-4 border-b border-gray-100 dark:border-gray-800/50 last:border-0 last:pb-0 group'>
                    <div class='w-10 h-10 rounded-xl bg-gradient-to-br from-brand-50 to-brand-100 dark:from-brand-900/40 dark:to-brand-800/20 flex flex-col items-center justify-center flex-shrink-0 border border-brand-200/50 dark:border-brand-700/30 group-hover:scale-110 transition-transform'>
                        <i class='fas fa-shopping-bag text-brand-600 dark:text-brand-400 text-sm'></i>
                    </div>
                    <div class='flex-1 min-w-0'>
                        <div class='flex justify-between items-start mb-1'>
                            <p class='text-sm text-gray-800 dark:text-gray-200'>
                                <span class='font-bold bg-brand-100 dark:bg-brand-900/30 px-1.5 py-0.5 rounded text-xs mr-1 text-brand-600 dark:text-brand-400'>Web</span>
                                Pedido de <span class='font-bold'><?= $customer ?></span>
                            </p>
                            <span class='text-[10px] font-bold text-gray-400 shrink-0 ml-2'><?= $timeago ?></span>
                        </div>
                        <div class='mt-2 p-3 bg-white dark:bg-slate-800 rounded-xl border border-gray-100 dark:border-gray-700/50 shadow-sm border-l-2 border-l-brand-500'>
                            <p class='text-xs text-gray-600 dark:text-gray-400 leading-relaxed mb-2'>
                                <span class='font-bold text-gray-800 dark:text-gray-200'>Artículos:</span> <?= htmlspecialchars($act['desc']) ?>
                            </p>
                            <div class='flex justify-between items-center'>
                                <span class='text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider <?= $badgeColor ?>'><?= $act['status'] ?></span>
                                <span class='font-black text-brand-600 dark:text-brand-400'>$<?= $total ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
    }
}
