<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6">
    <div class="flex items-center text-sm text-gray-500 mb-2">
        <a href="<?= BASE_URL ?>inventory" class="hover:text-brand-600 transition-colors">Inventario</a>
        <i class="fas fa-chevron-right mx-2 text-xs"></i>
        <span class="text-gray-800 font-medium">Nuevo Producto</span>
    </div>
    <h2 class="text-2xl font-bold text-gray-800">Agregar Nuevo Producto</h2>
</div>

<?php if (!empty($error)): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
            <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
        </div>
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>inventory/create" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Basic Info -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-2">Información Básica</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto *</label>
                <input type="text" name="name" required class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-colors">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select name="category_id" id="cat_select" class="w-full rounded-md border border-gray-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">-- Sin categoría --</option>
                        <?php if(!empty($categories)): foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
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
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; endif; ?>
                        <option value="new" class="font-bold text-brand-600">+ Otra (Añadir nueva)...</option>
                    </select>
                    <input type="text" name="new_brand" id="new_brand" placeholder="Nombre de la marca" class="hidden mt-2 w-full rounded-md border-brand-300 bg-brand-50 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Imagen del Producto (Opcional)</label>
                    <input type="file" name="image" accept="image/*" class="w-full rounded-md border border-gray-300 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors bg-white text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Volumen / Medida</label>
                    <select name="unit" id="unit_select" class="w-full rounded-md border border-gray-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="Unidad">Unidad / Pza</option>
                        <option value="Kilogramo">Kilogramos (kg)</option>
                        <option value="Gramo">Gramos (g)</option>
                        <option value="Litro">Litros (L)</option>
                        <option value="Mililitro">Mililitros (ml)</option>
                        <option value="Paquete">Paquete</option>
                        <option value="Saco">Saco</option>
                        <option value="Lata">Lata</option>
                        <option value="Lote">Lote</option>
                        <option value="new" class="font-bold text-brand-600">+ Otra unidad...</option>
                    </select>
                    <input type="text" name="new_unit" id="new_unit" placeholder="Ej: Rollo" class="hidden mt-2 w-full rounded-md border-brand-300 bg-brand-50 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500">
                </div>
            </div>
        </div>

        <!-- Inventory and Pricing -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-2">Gestión de Costos y Precios</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Costo</label>
                    <select name="cost_type" id="cost_type" class="w-full rounded-md border border-gray-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="unit">Por Unidad</option>
                        <option value="bulk">Por Bulto</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Moneda de Costo</label>
                    <select name="currency" class="w-full rounded-md border border-gray-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="USD">USD (Dólar)</option>
                        <option value="VES">VES (Bolívar)</option>
                        <option value="COP">COP (Peso Col.)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" id="label_cost">Costo Unitario *</label>
                    <input type="number" step="0.01" name="cost" id="input_cost" required class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div id="bulk_units_container" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unidades por Bulto *</label>
                    <input type="number" name="units_per_bulk" id="units_per_bulk" value="1" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">% Margen de Ganancia</label>
                    <div class="relative">
                        <input type="number" step="0.01" name="profit_margin" id="profit_margin" value="0.0" class="w-full rounded-md border border-gray-300 pr-8 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-sm">%</span>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio Final (Sugerido)</label>
                    <input type="number" step="0.01" name="price" id="input_price" required class="w-full bg-gray-50 rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 font-bold text-gray-700">
                </div>
            </div>

            <div class="flex items-center gap-2 mt-4 p-3 bg-gray-50 rounded border border-gray-200">
                <input type="checkbox" name="is_tax_exempt" id="is_tax_exempt" value="1" class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                <label for="is_tax_exempt" class="text-sm font-medium text-gray-700">Producto Exento de IVA (E)</label>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-800 border-b border-gray-100 pb-2 mt-6">Inventario</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock Inicial</label>
                    <input type="number" name="stock" value="0" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo</label>
                    <input type="number" name="min_stock" value="5" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors">
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic Attributes Section -->
    <div class="mt-8 border border-brand-100 bg-brand-50/30 rounded-lg p-5">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2">
            <div>
                <h3 class="text-lg font-semibold text-brand-900">Atributos Personalizados</h3>
                <p class="text-sm text-gray-600">Ideal para IMEI, Tallas, Colores, Lotes, etc.</p>
            </div>
            <button type="button" id="add-attr-btn" class="w-full sm:w-auto text-sm bg-white border border-brand-300 text-brand-700 hover:bg-brand-50 font-medium px-4 py-2 rounded shadow-sm flex items-center justify-center transition-colors">
                <i class="fas fa-plus mr-2 text-brand-500"></i> Agregar Atributo
            </button>
        </div>
        
        <div id="attributes-container" class="space-y-3">
            <div class="text-sm text-gray-500 italic p-4 text-center border-2 border-dashed border-gray-300 rounded" id="empty-attrs-msg">
                No has definido atributos personalizados. Presiona "Agregar" si los necesitas.
            </div>
        </div>
    </div>

    <div class="mt-8 pt-5 border-t border-gray-100 flex flex-col sm:flex-row justify-end gap-3">
        <a href="<?= BASE_URL ?>inventory" class="px-5 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium text-center transition-colors">Cancelar</a>
        <button type="submit" class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-md font-medium shadow-sm transition-colors flex justify-center items-center">
            <i class="fas fa-save mr-2"></i> Guardar Producto
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle new inputs logic
        const selects = ['cat', 'brand', 'unit'];
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

        // Dynamic Attributes logic
        const btnAdd = document.getElementById('add-attr-btn');
        const container = document.getElementById('attributes-container');
        const emptyMsg = document.getElementById('empty-attrs-msg');

        btnAdd.addEventListener('click', function() {
            if (emptyMsg) emptyMsg.style.display = 'none';
            
            const row = document.createElement('div');
            row.className = 'flex gap-3 items-center animate-fade-in-up bg-white p-2 rounded border border-gray-200 shadow-sm';
            row.innerHTML = `
                <div class="flex-1">
                    <input type="text" name="meta_key[]" placeholder="Atributo (Ej: Material)" required class="w-full text-sm rounded-md border border-gray-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="flex-1">
                    <input type="text" name="meta_value[]" placeholder="Valor (Ej: Plástico)" required class="w-full text-sm rounded-md border border-gray-300 px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <button type="button" class="remove-attr text-gray-400 hover:text-red-500 p-2 focus:outline-none transition-colors" title="Eliminar atributo">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            
            container.appendChild(row);
            
            row.querySelector('.remove-attr').addEventListener('click', function() {
                container.removeChild(row);
                if (container.children.length === 1 && emptyMsg) { 
                    emptyMsg.style.display = 'block';
                }
            });
        });
    });
</script>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out forwards;
}
</style>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
