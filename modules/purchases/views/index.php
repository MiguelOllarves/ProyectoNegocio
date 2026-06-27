<?php include __DIR__ . '/../../../includes/header.php'; ?>
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3" x-data="{ openModal: false }">
    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white">Compras / Recepción</h2>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Historial de entradas de mercancía</p>
    </div>
    <button @click="openModal = true" class="bg-gradient-to-r from-brand-600 to-accent-600 hover:from-brand-500 hover:to-accent-500 text-white font-bold px-5 py-2.5 rounded-lg shadow-sm transition-all flex items-center justify-center w-full sm:w-auto text-sm">
        <i class="fas fa-plus mr-2"></i> Nueva Compra
    </button>

    <!-- MODAL: Nueva Compra -->
    <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-start justify-center min-h-screen px-4 pt-10 pb-20 text-center sm:p-0">
            <div x-show="openModal" x-transition.opacity @click="openModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div x-show="openModal" x-transition class="relative inline-block w-full max-w-3xl p-6 md:p-8 text-left align-middle bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 my-8"
                 x-data="purchaseForm()">
                <div class="flex justify-between items-center mb-6 border-b border-gray-100 dark:border-gray-800 pb-4">
                    <h3 class="text-xl font-black text-gray-800 dark:text-white flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 dark:bg-brand-900/30 text-brand-600 dark:text-brand-400 flex items-center justify-center mr-3"><i class="fas fa-dolly"></i></div>
                        Registrar Nueva Compra
                    </h3>
                    <button @click="openModal = false" class="text-gray-400 hover:text-red-500 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-red-50 dark:hover:bg-red-900/20"><i class="fas fa-times"></i></button>
                </div>

                <!-- Supplier select -->
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
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Notas</label>
                        <input type="text" x-model="notes" placeholder="Opcional..." class="w-full bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <!-- Add Product Row -->
                <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-4 border border-gray-100 dark:border-gray-700 mb-4">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Agregar Productos</h4>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <select x-model="newItem.product_id" class="flex-1 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">Seleccionar producto...</option>
                            <?php if(!empty($products)): foreach($products as $prod): ?>
                                <option value="<?= $prod['id'] ?>" data-name="<?= htmlspecialchars($prod['name']) ?>"><?= htmlspecialchars($prod['name']) ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                        <input type="number" x-model.number="newItem.quantity" min="1" placeholder="Cant." class="w-24 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <input type="number" step="0.01" x-model.number="newItem.cost" placeholder="C/U $" class="w-28 bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <button @click="addItem()" type="button" class="bg-brand-600 hover:bg-brand-700 text-white font-bold px-4 py-2 rounded-lg text-sm transition-colors"><i class="fas fa-plus"></i></button>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="overflow-x-auto mb-4" x-show="items.length > 0">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-slate-800 border-b text-gray-500 dark:text-gray-400 uppercase text-[10px] tracking-widest font-black">
                                <th class="p-3">Producto</th>
                                <th class="p-3 text-center">Cant.</th>
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
                                <td colspan="3" class="p-3 text-right text-sm font-black text-gray-600 dark:text-gray-300">TOTAL:</td>
                                <td class="p-3 text-right text-lg font-black text-brand-700 dark:text-brand-400" x-text="'$' + total.toFixed(2)"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
                    <button type="button" @click="openModal = false" class="px-5 py-2.5 bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 font-medium text-sm">Cancelar</button>
                    <button type="button" @click="submit()" :disabled="items.length === 0" class="px-6 py-2.5 bg-gradient-to-r from-brand-600 to-accent-600 text-white rounded-lg font-bold shadow-md hover:shadow-lg transition-all text-sm flex items-center disabled:opacity-50">
                        <i class="fas fa-save mr-2"></i> Registrar Compra
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function purchaseForm() {
    return {
        supplierId: '',
        notes: '',
        items: [],
        newItem: { product_id: '', quantity: 1, cost: 0 },
        get total() { return this.items.reduce((sum, i) => sum + (i.quantity * i.cost), 0); },
        addItem() {
            if (!this.newItem.product_id || this.newItem.quantity <= 0) return;
            const sel = document.querySelector(`option[value="${this.newItem.product_id}"]`);
            this.items.push({
                product_id: this.newItem.product_id,
                name: sel ? sel.textContent : 'Producto',
                quantity: this.newItem.quantity,
                cost: this.newItem.cost || 0
            });
            this.newItem = { product_id: '', quantity: 1, cost: 0 };
        },
        submit() {
            if (this.items.length === 0) return;
            const payload = {
                supplier_id: this.supplierId || null,
                notes: this.notes,
                items: this.items.map(i => ({
                    product_id: i.product_id,
                    quantity: i.quantity,
                    cost_per_unit: i.cost
                }))
            };
            fetch('<?= BASE_URL ?>purchases/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error al procesar');
                }
            })
            .catch(() => alert('Error de red'));
        }
    }
}
</script>

<!-- Search + Print Bar -->
<div class="flex flex-col sm:flex-row items-center gap-3 mb-4">
    <div class="relative flex-1 w-full">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" data-table-search="#purchases-tbody" placeholder="Buscar compra, proveedor, ID..." class="w-full bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 rounded-lg pl-10 pr-4 py-2.5 text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all shadow-sm">
    </div>
    <button onclick="printPage()" class="no-print bg-white dark:bg-slate-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors shadow-sm flex items-center gap-2">
        <i class="fas fa-print"></i> Imprimir
    </button>
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
            <tbody id="purchases-tbody" class="divide-y divide-gray-100 dark:divide-gray-700">
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
