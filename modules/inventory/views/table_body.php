<?php if (empty($products)): ?>
    <tr>
        <td colspan="5" class="p-8 text-center text-gray-400">
            <i class="fas fa-box-open text-4xl mb-3 text-gray-300 block"></i>
            No hay productos registrados.<br>
        </td>
    </tr>
<?php else: foreach ($products as $p): ?>
    <tr class="hover:bg-gray-50 transition-colors animate-fade-in-up">
        <td class="p-4">
            <div class="flex items-center gap-3">
                <?php if(!empty($p['image'])): ?>
                    <img src="<?= BASE_URL . '../' . htmlspecialchars($p['image']) ?>" class="w-10 h-10 rounded-lg object-cover shadow-sm bg-gray-50 border border-gray-200">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 border border-gray-200 shadow-sm"><i class="fas fa-image"></i></div>
                <?php endif; ?>
                <div class="font-bold text-sm text-gray-900"><?= htmlspecialchars($p['name']) ?></div>
            </div>
        </td>
        <td class="p-4 text-sm text-gray-600">
            <div class="flex flex-col gap-1 items-start">
                <span class="bg-blue-50 border border-blue-100 text-blue-700 py-0.5 px-2 rounded-md text-xs font-semibold"><i class="fas fa-tag mr-1 opacity-70"></i><?= htmlspecialchars($p['brand_name'] ?? 'Genérico') ?></span>
                <span class="bg-gray-100 border border-gray-200 text-gray-700 py-0.5 px-2 rounded-md text-xs"><i class="fas fa-folder mr-1 opacity-70"></i><?= htmlspecialchars($p['category_name'] ?? 'Sin Categoría') ?></span>
            </div>
        </td>
        <td class="p-4 text-center">
            <?php $stockClass = $p['stock'] <= $p['min_stock'] ? 'text-red-700 bg-red-100' : 'text-green-700 bg-green-100'; ?>
            <span class="inline-block px-2 py-1 rounded-md text-sm font-semibold <?= $stockClass ?>"><?= $p['stock'] ?></span>
        </td>
        <td class="p-4 text-sm font-bold text-gray-800 text-right">$<?= number_format($p['price'], 2) ?></td>
        <td class="p-4 text-right space-x-1">
            <a href="<?= BASE_URL ?>inventory/edit/<?= $p['id'] ?>" class="inline-block text-gray-400 hover:text-amber-500 bg-gray-50 p-2 rounded-lg" title="Editar"><i class="fas fa-edit"></i></a>
        </td>
    </tr>
<?php endforeach; endif; ?>
