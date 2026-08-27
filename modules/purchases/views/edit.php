<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white flex items-center">
            <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center mr-3"><i class="fas fa-edit"></i></div>
            Editar Compra - CMP-<?= str_pad($purchase['id'], 4, '0', STR_PAD_LEFT) ?>
        </h2>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Modifica los detalles y los ítems de esta compra</p>
    </div>
    <a href="<?= BASE_URL ?>purchases" class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 px-5 py-2.5 rounded-lg text-sm font-bold shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Volver a Compras
    </a>
</div>

<div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 md:p-8" x-data="editPurchaseForm()">
    
    <!-- Supplier -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Proveedor</label>
            <select x-model="supplierId" class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Sin proveedor</option>
                <?php if(!empty($suppliers)): foreach($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; endif; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Notas (Opcional)</label>
            <input type="text" x-model="notes" placeholder="Agrega alguna nota..." class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
    </div>

    <!-- Product Row -->
    <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-4 border border-gray-100 dark:border-gray-700 mb-6">
        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Agregar Productos a la Compra</h4>
        <div class="flex flex-col sm:flex-row gap-3">
            <select x-model="newItem.product_id" @change="fetchPresentations()" class="flex-1 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Seleccionar producto...</option>
                <?php if(!empty($products)): foreach($products as $prod): ?>
                    <option value="<?= $prod['id'] ?>" data-name="<?= htmlspecialchars($prod['name']) ?>"><?= htmlspecialchars($prod['name']) ?></option>
                <?php endforeach; endif; ?>
            </select>
            <select x-model="newItem.presentation_id" :disabled="presentations.length === 0" class="flex-1 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:opacity-60">
                <option value="" x-text="presentations.length === 0 ? '-- Seleccione un Producto --' : 'Seleccione Presentación...'"></option>
                <template x-for="p in presentations" :key="p.id">
                    <option :value="p.id" x-text="p.name + ' (Trae ' + p.quantity + ')'"></option>
                </template>
            </select>
            <input type="number" x-model.number="newItem.quantity" min="1" placeholder="Emps." class="w-24 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
            <input type="number" step="0.01" min="0" x-model.number="newItem.cost" placeholder="Costo Emp $" class="w-32 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
            <button @click="addItem()" type="button" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-4 py-2 rounded-lg text-sm transition-colors shadow-sm"><i class="fas fa-plus"></i></button>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto mb-6" x-show="items.length > 0">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-slate-800 border-b text-gray-500 dark:text-gray-400 uppercase text-[10px] tracking-widest font-black">
                    <th class="p-3">Producto y Presentación</th>
                    <th class="p-3 text-center">Cant. Emp.</th>
                    <th class="p-3 text-right">C/U</th>
                    <th class="p-3 text-right">Subtotal</th>
                    <th class="p-3 text-center">X</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(item, idx) in items" :key="idx">
                    <tr class="border-b border-gray-100 dark:border-gray-700">
                        <td class="p-3 text-sm font-semibold text-gray-800 dark:text-gray-100" x-text="item.name"></td>
                        <td class="p-3 text-sm text-center" x-text="item.quantity"></td>
                        <td class="p-3 text-sm text-right" x-text="'$' + item.cost.toFixed(2)"></td>
                        <td class="p-3 text-sm text-right font-bold text-brand-600 dark:text-brand-400" x-text="'$' + (item.quantity * item.cost).toFixed(2)"></td>
                        <td class="p-3 text-center"><button type="button" @click="items.splice(idx, 1)" class="text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button></td>
                    </tr>
                </template>
            </tbody>
            <tfoot>
                <tr class="bg-brand-50 dark:bg-brand-900/20">
                    <td colspan="4" class="p-3 text-right text-sm font-black text-gray-600 dark:text-gray-300">TOTAL:</td>
                    <td class="p-3 text-right text-lg font-black text-brand-700 dark:text-brand-400" x-text="'$' + total.toFixed(2)"></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Actions -->
    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
        <button type="button" @click="submit()" :disabled="items.length === 0" class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg font-bold shadow-md hover:shadow-lg transition-all text-sm flex items-center disabled:opacity-50">
            <i class="fas fa-save mr-2"></i> Guardar Cambios
        </button>
    </div>

</div>

<?php 
// Convert PHP data to Javascript variables safely
$jsItems = array_map(function($i) {
    return [
        'product_id' => $i['product_id'],
        'name' => $i['name'] . (isset($i['presentation_name']) ? ' - ' . $i['presentation_name'] : ''),
        'presentation_id' => $i['presentation_id'] ?? null,
        'quantity' => $i['quantity'],
        'cost' => floatval($i['cost_per_unit'])
    ];
}, $purchase['items']);
?>

<script>
function editPurchaseForm() {
    return {
        supplierId: '<?= $purchase['supplier_id'] ?? '' ?>',
        notes: <?= json_encode($purchase['notes'] ?? '') ?>,
        items: <?= json_encode($jsItems) ?>,
        presentations: [],
        newItem: { product_id: '', quantity: 1, cost: 0, presentation_id: '' },
        get total() { return this.items.reduce((sum, i) => sum + (i.quantity * i.cost), 0); },
        async fetchPresentations() {
            this.presentations = [];
            this.newItem.presentation_id = '';
            if(!this.newItem.product_id) return;
            try {
                let res = await fetch('<?= BASE_URL ?>inventory/edit/' + this.newItem.product_id);
                let data = await res.json();
                if(data.presentations && data.presentations.length > 0) {
                    this.presentations = data.presentations;
                    this.newItem.presentation_id = this.presentations[0].id;
                }
            } catch(e) {}
        },
        addItem() {
            if (!this.newItem.product_id || !this.newItem.presentation_id || this.newItem.quantity <= 0) {
                Swal.fire('Error', 'Completa los datos de la presentación y cantidad correctamente.', 'warning');
                return;
            }
            const sel = document.querySelector(`option[value="${this.newItem.product_id}"]`);
            const p = this.presentations.find(x => String(x.id) === String(this.newItem.presentation_id));
            const pName = p ? p.name : '';
            this.items.push({
                product_id: this.newItem.product_id,
                presentation_id: this.newItem.presentation_id,
                name: (sel ? sel.textContent : 'Producto') + ' - ' + pName,
                quantity: this.newItem.quantity,
                cost: this.newItem.cost || 0
            });
            this.newItem = { product_id: '', quantity: 1, cost: 0, presentation_id: '' };
            this.presentations = [];
        },
        submit() {
            if (this.items.length === 0) return;
            const payload = {
                supplier_id: this.supplierId || null,
                notes: this.notes,
                items: this.items.map(i => ({
                    product_id: i.product_id,
                    presentation_id: i.presentation_id,
                    quantity: i.quantity,
                    cost: i.cost
                }))
            };
            
            Swal.fire({ title: 'Procesando edición...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});

            fetch('<?= BASE_URL ?>purchases/edit/<?= $purchase['id'] ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Compra Actualizada!',
                        text: 'Los cambios se han guardado con éxito.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: true,
                        confirmButtonText: 'Terminar',
                        confirmButtonColor: '#10b981',
                        customClass: { popup: 'rounded-2xl' }
                    }).then(() => {
                        window.location.href = '<?= BASE_URL ?>purchases';
                    });
                } else {
                    Swal.fire('Error', data.message || 'Error al procesar', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Error de red en el servidor', 'error'));
        }
    }
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
