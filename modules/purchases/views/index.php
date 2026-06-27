<?php include __DIR__ . '/../../../includes/header.php'; ?>
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white">Compras / Recepción</h2>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Historial de entradas de mercancía</p>
    </div>
    <a href="<?= BASE_URL ?>purchases/create" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm flex items-center w-full sm:w-auto justify-center">
        <i class="fas fa-plus mr-2"></i> Nueva Compra
    </a>
</div>
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-[500px] w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700 border-b text-gray-500 dark:text-gray-300 uppercase text-xs tracking-wider">
                    <th class="p-4 font-semibold"># ID</th>
                    <th class="p-4 font-semibold">Proveedor</th>
                    <th class="p-4 font-semibold text-right">Total</th>
                    <th class="p-4 font-semibold">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php if (empty($purchases)): ?>
                    <tr><td colspan="4" class="p-8 text-center text-gray-400 dark:text-gray-500"><i class="fas fa-dolly text-4xl mb-3 block opacity-30"></i>No hay compras registradas.</td></tr>
                <?php else: foreach ($purchases as $p): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="p-4 font-mono text-sm text-brand-600 dark:text-brand-400 font-bold">CMP-<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td class="p-4 text-sm text-gray-800 dark:text-gray-100 font-semibold"><?= htmlspecialchars($p['supplier_name'] ?? 'Sin proveedor') ?></td>
                        <td class="p-4 text-sm font-bold text-gray-800 dark:text-gray-200 text-right">$<?= number_format($p['total'], 2) ?></td>
                        <td class="p-4 text-sm text-gray-500 dark:text-gray-400"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../../includes/footer.php'; ?>
