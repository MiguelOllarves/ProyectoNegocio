<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div class="flex items-center gap-3">
        <a href="<?= BASE_URL ?>reports" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 border border-gray-200 dark:border-gray-700 hover:border-brand-300 dark:hover:border-brand-700 flex items-center justify-center transition-all shadow-sm">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Kardex de Inventario</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Auditoría detallada de movimientos por producto</p>
        </div>
    </div>
</div>

<!-- Filtro de Producto -->
<div class="bg-white dark:bg-slate-800 p-5 rounded-xl shadow-sm mb-6 border border-gray-100 dark:border-gray-800 flex items-center gap-4">
    <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-900/30 font-bold text-brand-600 dark:text-brand-400 flex items-center justify-center shrink-0">
        <i class="fas fa-search"></i>
    </div>
    <form method="GET" action="<?= BASE_URL ?>reports/kardex" class="flex flex-1 flex-col sm:flex-row items-start sm:items-end gap-4 w-full">
        <div class="flex-1 w-full">
            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Filtrar por Producto</label>
            <select name="product_id" class="w-full px-3 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-brand-500 h-10 cursor-pointer">
                <option value="">-- Todos los productos --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $selectedProduct == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?> (SKU: <?= htmlspecialchars($p['sku']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-bold h-10 px-5 rounded-lg shadow-sm shadow-brand-500/20 transition-all text-sm w-full sm:w-auto">
            Buscar
        </button>
    </form>
</div>

<!-- Tabla Kardex -->
<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
    <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-slate-800/50 flex justify-between items-center">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white"><i class="fas fa-history text-brand-500 mr-2"></i>Historial de Movimientos</h3>
        <button onclick="window.print()" class="text-xs text-brand-600 dark:text-brand-400 font-bold hover:underline">
            <i class="fas fa-print mr-1"></i> Imprimir
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-900 border-b border-gray-100 dark:border-gray-800 text-gray-500 dark:text-gray-400 uppercase text-[10px] tracking-widest font-black">
                    <th class="p-4 whitespace-nowrap">Fecha y Hora</th>
                    <th class="p-4">Producto</th>
                    <th class="p-4">Tipo Movto.</th>
                    <th class="p-4 text-center">Cant.</th>
                    <th class="p-4 text-center">Stock Resultante</th>
                    <th class="p-4">Referencia / Nota</th>
                    <th class="p-4">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                <?php if (empty($kardex)): ?>
                <tr><td colspan="7" class="p-12 text-center text-gray-400 dark:text-gray-500 font-medium"><i class="fas fa-inbox text-3xl block mb-3 opacity-30"></i>No se registran movimientos para estos criterios.</td></tr>
                <?php else: foreach ($kardex as $k): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-900/50 transition-colors">
                    <td class="p-4 text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap">
                        <?= date('d/m/Y', strtotime($k['created_at'])) ?>
                        <span class="text-xs ml-1 bg-gray-100 dark:bg-gray-800 px-1.5 rounded text-gray-400 dark:text-gray-500"><?= date('H:i', strtotime($k['created_at'])) ?></span>
                    </td>
                    <td class="p-4 font-bold text-gray-800 dark:text-gray-200">
                        <?= htmlspecialchars($k['product_name']) ?>
                        <div class="text-[10px] uppercase text-gray-400 font-medium tracking-widest mt-0.5">SKU: <?= htmlspecialchars($k['sku']) ?></div>
                    </td>
                    <td class="p-4">
                        <?php if ($k['type'] === 'entrada_compra' || $k['type'] === 'AJUSTE_ENTRADA'): ?>
                            <span class="text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider flex items-center w-fit border border-green-100 dark:border-green-800/30">
                                <i class="fas fa-arrow-down mr-1.5"></i> Entrada
                            </span>
                        <?php elseif ($k['type'] === 'salida_venta' || $k['type'] === 'AJUSTE_SALIDA' || $k['type'] === 'sale'): ?>
                            <span class="text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider flex items-center w-fit border border-red-100 dark:border-red-800/30">
                                <i class="fas fa-arrow-up mr-1.5"></i> Salida
                            </span>
                        <?php else: ?>
                            <span class="text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider flex items-center w-fit border border-gray-200 dark:border-gray-700">
                                <i class="fas fa-circle-dot mr-1.5 text-[8px]"></i> <?= htmlspecialchars($k['type']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="p-4 text-center font-black <?= (strpos($k['type'], 'salida') === false && $k['type'] !== 'sale' && $k['type'] !== 'AJUSTE_SALIDA') ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>">
                        <?= (strpos($k['type'], 'salida') === false && $k['type'] !== 'sale' && $k['type'] !== 'AJUSTE_SALIDA') ? '+' : '-' ?><?= $k['quantity'] ?>
                    </td>
                    <td class="p-4 text-center font-black text-brand-600 dark:text-brand-400 bg-brand-50/50 dark:bg-brand-900/10">
                        <?= $k['stock_after'] ?? 'N/A' ?>
                    </td>
                    <td class="p-4">
                        <div class="text-[10px] uppercase font-bold text-gray-400 tracking-wider"><?= htmlspecialchars($k['reference_type']) ?> #<?= htmlspecialchars($k['reference_id'] ?? '') ?></div>
                        <div class="text-xs font-medium text-gray-700 dark:text-gray-300"><?= htmlspecialchars($k['note'] ?? '') ?></div>
                    </td>
                    <td class="p-4 text-xs font-bold text-gray-500 dark:text-gray-400 flex items-center">
                        <div class="w-6 h-6 rounded bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-gray-300 flex items-center justify-center mr-2 uppercase">
                            <?= substr(htmlspecialchars($k['user_name'] ?? 'S'), 0, 1) ?>
                        </div>
                        <?= htmlspecialchars($k['user_name'] ?? 'Sistema') ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
