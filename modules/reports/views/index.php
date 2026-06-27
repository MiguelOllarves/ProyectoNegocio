<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Reportes e Inteligencia</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Análisis financiero y de existencias</p>
    </div>
    
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>reports/kardex" class="bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-slate-700 font-bold py-2 px-4 rounded-lg shadow-sm transition-all text-sm flex items-center w-full sm:w-auto justify-center">
            <i class="fas fa-exchange-alt mr-2"></i> Ver Kardex
        </a>
    </div>
</div>

<!-- Filtro de Fecha -->
<div class="bg-white dark:bg-slate-800 p-3 sm:p-5 rounded-xl shadow-sm mb-6 border border-gray-100 dark:border-gray-800">
    <form method="GET" action="<?= BASE_URL ?>reports" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
        <div class="flex items-center gap-3 sm:shrink-0">
            <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-900/30 font-bold text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0">
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>
        <div class="flex-1 grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Desde</label>
                <input type="date" name="start" value="<?= htmlspecialchars($start) ?>" class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500 h-10">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Hasta</label>
                <input type="date" name="end" value="<?= htmlspecialchars($end) ?>" class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500 h-10">
            </div>
        </div>
        <button type="submit" class="bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-bold h-10 px-5 rounded-lg shadow-sm shadow-brand-500/20 transition-all text-sm w-full sm:w-auto">
            Aplicar
        </button>
    </form>
</div>

<!-- Analytics KPIs -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Ventas -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 flex items-center hover:-translate-y-1 transition-transform relative overflow-hidden group">
        <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 flex items-center justify-center text-xl mr-4 border border-brand-100 dark:border-brand-800/50">
            <i class="fas fa-hand-holding-dollar"></i>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400 mb-1">Ventas (Ingresos)</p>
            <p class="text-2xl font-black text-gray-800 dark:text-white">$<?= number_format($summary['income'], 2) ?></p>
        </div>
    </div>

    <!-- Inventario -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 flex items-center hover:-translate-y-1 transition-transform relative overflow-hidden group">
        <div class="w-12 h-12 rounded-xl bg-accent-50 dark:bg-accent-900/30 text-accent-600 dark:text-accent-400 flex items-center justify-center text-xl mr-4 border border-accent-100 dark:border-accent-800/50">
            <i class="fas fa-cubes"></i>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400 mb-1">Costo Inventario</p>
            <p class="text-2xl font-black text-gray-800 dark:text-white">$<?= number_format($summary['inventory_value'], 2) ?></p>
        </div>
    </div>

    <!-- Rendimiento -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 flex items-center hover:-translate-y-1 transition-transform relative overflow-hidden group">
        <div class="w-12 h-12 rounded-xl <?= $summary['profit'] >= 0 ? 'bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 border-green-100 dark:border-green-800/50' : 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border-red-100 dark:border-red-800/50' ?> flex items-center justify-center text-xl mr-4 border">
            <i class="fas fa-chart-line"></i>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400 mb-1">Ganancia Neta</p>
            <p class="text-2xl font-black <?= $summary['profit'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                $<?= number_format($summary['profit'], 2) ?>
            </p>
        </div>
    </div>

    <!-- Impuestos -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 flex items-center hover:-translate-y-1 transition-transform relative overflow-hidden group">
        <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl mr-4 border border-purple-100 dark:border-purple-800/50">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div>
            <p class="text-[11px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400 mb-1">IVA Recaudado</p>
            <p class="text-2xl font-black text-gray-800 dark:text-white">$<?= number_format($summary['taxes'], 2) ?></p>
        </div>
    </div>
</div>

<!-- Detailed Sales Table -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
    <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-slate-800/50">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white"><i class="fas fa-list text-brand-500 mr-2"></i>Detalle de Transacciones (<?= $start ?> al <?= $end ?>)</h3>
        <button onclick="window.print()" class="text-sm text-gray-500 hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
            <i class="fas fa-print mr-1"></i> Imprimir
        </button>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-[700px] w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-900 border-b border-gray-100 dark:border-gray-800 text-gray-500 dark:text-gray-400 uppercase text-[10px] tracking-widest font-black">
                    <th class="p-4 whitespace-nowrap">Fecha</th>
                    <th class="p-4">ID</th>
                    <th class="p-4 min-w-[200px]">Detalle</th>
                    <th class="p-4 text-right">Total ($)</th>
                    <th class="p-4 text-right">Costo ($)</th>
                    <th class="p-4 text-right">Ganancia ($)</th>
                    <th class="p-4 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                <?php if(empty($sales)): ?>
                <tr><td colspan="7" class="p-8 text-center text-gray-400 dark:text-gray-500 font-medium">No se registraron ventas en este período.</td></tr>
                <?php else: foreach($sales as $sale): 
                    $ganancia = $sale['total'] - $sale['iva'] - $sale['cost_calculated'];
                ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-900/50 transition-colors">
                    <td class="p-4 text-gray-600 dark:text-gray-400 font-medium whitespace-nowrap">
                        <?= date('d/m/Y H:i', strtotime($sale['date'])) ?>
                    </td>
                    <td class="p-4">
                        <span class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs font-bold text-gray-600 dark:text-gray-300">#<?= str_pad($sale['id'], 5, '0', STR_PAD_LEFT) ?></span>
                    </td>
                    <td class="p-4">
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate max-w-xs" title="<?= htmlspecialchars($sale['detail']) ?>">
                            <?= htmlspecialchars($sale['detail']) ?>
                        </p>
                    </td>
                    <td class="p-4 text-right font-black text-gray-800 dark:text-gray-200">
                        <?= number_format($sale['total'], 2) ?>
                    </td>
                    <td class="p-4 text-right font-medium text-gray-500 dark:text-gray-400">
                        <?= number_format($sale['cost_calculated'], 2) ?>
                    </td>
                    <td class="p-4 text-right font-black <?= $ganancia >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                        <?= $ganancia > 0 ? '+' : '' ?><?= number_format($ganancia, 2) ?>
                    </td>
                    <td class="p-4 text-center">
                        <a href="<?= BASE_URL ?>sales/receipt/<?= $sale['id'] ?>" target="_blank" class="text-gray-400 hover:text-brand-600 bg-gray-50 hover:bg-brand-50 dark:bg-slate-800 dark:hover:bg-brand-900/30 p-2 rounded-lg transition-colors" title="Ver Recibo">
                            <i class="fas fa-file-invoice"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
