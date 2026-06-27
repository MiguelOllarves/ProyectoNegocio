<?php if (empty($products)): ?>
    <tr>
        <td colspan="5" class="p-8 text-center text-gray-400">
            <i class="fas fa-box-open text-4xl mb-3 text-gray-300 block"></i>
            No hay productos registrados.<br>
        </td>
    </tr>
<?php else: foreach ($products as $p): ?>
    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors animate-fade-in-up">
        <td class="p-4">
            <div class="flex items-center gap-3">
                <?php if(!empty($p['image'])): ?>
                    <img src="<?= BASE_URL . '../' . htmlspecialchars($p['image']) ?>" class="w-10 h-10 rounded-lg object-cover shadow-sm bg-gray-50 border border-gray-200 dark:border-gray-700">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-slate-700 flex items-center justify-center text-gray-400 border border-gray-200 dark:border-gray-700 shadow-sm"><i class="fas fa-image"></i></div>
                <?php endif; ?>
                <div>
                    <div class="font-bold text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($p['name']) ?></div>
                    <?php if(!empty($p['barcode'])): ?>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 font-mono cursor-pointer hover:text-brand-500" onclick="openQrModal('<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($p['barcode'], ENT_QUOTES) ?>')"><i class="fas fa-barcode mr-1"></i><?= htmlspecialchars($p['barcode']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </td>
        <td class="p-4 text-sm text-gray-600 dark:text-gray-300">
            <div class="flex flex-col gap-1 items-start">
                <span class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 text-blue-700 dark:text-blue-300 py-0.5 px-2 rounded-md text-xs font-semibold"><i class="fas fa-tag mr-1 opacity-70"></i><?= htmlspecialchars($p['brand_name'] ?? 'Genérico') ?></span>
                <span class="bg-gray-100 dark:bg-slate-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 py-0.5 px-2 rounded-md text-xs"><i class="fas fa-folder mr-1 opacity-70"></i><?= htmlspecialchars($p['category_name'] ?? 'Sin Categoría') ?></span>
            </div>
        </td>
        <td class="p-4 text-center">
            <?php $stockClass = $p['stock'] <= $p['min_stock'] ? 'text-red-700 bg-red-100 dark:text-red-300 dark:bg-red-900/30' : 'text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30'; ?>
            <span class="inline-block px-2 py-1 rounded-md text-sm font-semibold <?= $stockClass ?>"><?= $p['stock'] ?></span>
        </td>
        <td class="p-4 text-sm font-bold text-gray-800 dark:text-gray-200 text-right">$<?= number_format($p['price'], 2) ?></td>
        <td class="p-4 text-right space-x-1">
            <button onclick="editProduct(<?= $p['id'] ?>)" class="inline-block text-gray-400 hover:text-amber-500 bg-gray-50 dark:bg-slate-700 p-2 rounded-lg transition-colors" title="Editar"><i class="fas fa-edit"></i></button>
            <button onclick="if(confirm('¿Eliminar este producto?')) deleteProduct(<?= $p['id'] ?>)" class="inline-block text-gray-400 hover:text-red-500 bg-gray-50 dark:bg-slate-700 p-2 rounded-lg transition-colors" title="Eliminar"><i class="fas fa-trash"></i></button>
        </td>
    </tr>
<?php endforeach; endif; ?>
