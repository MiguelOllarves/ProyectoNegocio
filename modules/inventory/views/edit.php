<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6">
    <div class="flex items-center text-sm text-gray-500 mb-2">
        <a href="<?= BASE_URL ?>inventory" class="hover:text-brand-600 transition-colors">Inventario</a>
        <i class="fas fa-chevron-right mx-2 text-xs"></i>
        <span class="text-gray-800 font-medium">Editar Producto</span>
    </div>
    <h2 class="text-2xl font-bold text-gray-800">Editar Producto (#<?= htmlspecialchars($product['sku']) ?>)</h2>
</div>

<?php if (!empty($error)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
            <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
        </div>
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>inventory/edit/<?= $product['id'] ?>" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Basic Info -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-2">Información Básica</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select name="category_id" id="cat_select" class="w-full rounded-md border border-gray-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">-- Sin categoría --</option>
                        <?php if(!empty($categories)): foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id'] == $product['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; endif; ?>
                        <option value="new" class="font-bold text-brand-600">+ Otra (Añadir nueva)...</option>
                    </select>
                    <input type="text" name="new_category" id="new_category" placeholder="Nombre de categoría" class="hidden mt-2 w-full rounded-md border-brand-300 bg-brand-50 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Marca (Bodega)</label>
                    <select name="brand_id" id="brand_select" class="w-full rounded-md border border-gray-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">-- Sin marca --</option>
                        <?php if(!empty($brands)): foreach($brands as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $b['id'] == $product['brand_id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; endif; ?>
                        <option value="new" class="font-bold text-brand-600">+ Otra (Añadir nueva)...</option>
                    </select>
                    <input type="text" name="new_brand" id="new_brand" placeholder="Nombre de la marca" class="hidden mt-2 w-full rounded-md border-brand-300 bg-brand-50 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Imagen del Producto</label>
                    <input type="file" name="image" accept="image/*" class="w-full rounded-md border border-gray-300 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors bg-white text-sm">
                    <?php if ($product['image']): ?>
                        <p class="text-xs mt-1 text-gray-500">Ya tiene imagen asignada.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código de Barras</label>
                    <input type="text" name="barcode" value="<?= htmlspecialchars($product['barcode']) ?>" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">
                </div>
            </div>
        </div>

        <!-- Inventory and Pricing -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-2">Precio y Existencias</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Precio Unitario *</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>" required class="w-full rounded-md border border-gray-300 pl-7 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                    <input type="number" name="stock" value="<?= htmlspecialchars($product['stock']) ?>" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo</label>
                    <input type="number" name="min_stock" value="<?= htmlspecialchars($product['min_stock']) ?>" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 pt-5 border-t border-gray-100 flex flex-col sm:flex-row justify-end gap-3">
        <a href="<?= BASE_URL ?>inventory" class="px-5 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium text-center transition-colors">Cancelar</a>
        <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-md font-medium shadow-sm transition-colors flex justify-center items-center">
            <i class="fas fa-save mr-2"></i> Guardar Cambios
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selects = ['cat', 'brand'];
        selects.forEach(pref => {
            const select = document.getElementById(pref + '_select');
            const input = document.getElementById('new_' + (pref === 'cat' ? 'category' : pref));
            if(select && input) {
                select.addEventListener('change', function() {
                    if(this.value === 'new') {
                        input.classList.remove('hidden');
                        input.focus();
                    } else {
                        input.classList.add('hidden');
                        input.value = '';
                    }
                });
            }
        });
    });
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
