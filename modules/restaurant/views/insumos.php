<?php include __DIR__ . '/../../../includes/header.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-gray-800 dark:text-white">🧂 Insumos de Cocina</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Inventario separado de ingredientes para recetas (kg, g, L, ml)</p>
        </div>
        <div class="flex gap-2">
            <a href="<?= BASE_URL ?>restaurant" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-bold hover:bg-gray-200 transition">
                <i class="fas fa-utensils mr-1"></i> Ver Platos
            </a>
            <button onclick="openModal()" class="px-4 py-2 bg-brand-600 text-white rounded-xl text-sm font-bold hover:bg-brand-700 transition shadow-lg shadow-brand-500/30">
                <i class="fas fa-plus mr-1"></i> Nuevo Insumo
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase">Total Insumos</p>
            <p class="text-2xl font-black text-gray-800 dark:text-white"><?= count($ingredients) ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase">Stock Bajo</p>
            <p class="text-2xl font-black text-red-500"><?= count(array_filter($ingredients, fn($i) => $i['stock'] <= $i['min_stock'] && $i['stock'] > 0)) ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase">Agotados</p>
            <p class="text-2xl font-black text-gray-800 dark:text-white"><?= count(array_filter($ingredients, fn($i) => $i['stock'] <= 0)) ?></p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
            <p class="text-xs font-bold text-gray-400 uppercase">Valor Total</p>
            <p class="text-2xl font-black text-emerald-600">$<?= number_format(array_sum(array_map(fn($i) => $i['stock'] * $i['cost_per_unit'], $ingredients)), 2) ?></p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="text-left p-3 text-xs font-bold text-gray-500 dark:text-gray-300 uppercase">Insumo</th>
                        <th class="text-center p-3 text-xs font-bold text-gray-500 dark:text-gray-300 uppercase">Unidad</th>
                        <th class="text-center p-3 text-xs font-bold text-gray-500 dark:text-gray-300 uppercase">Stock</th>
                        <th class="text-center p-3 text-xs font-bold text-gray-500 dark:text-gray-300 uppercase">Costo/Unidad</th>
                        <th class="text-center p-3 text-xs font-bold text-gray-500 dark:text-gray-300 uppercase">Proveedor</th>
                        <th class="text-center p-3 text-xs font-bold text-gray-500 dark:text-gray-300 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?php if (empty($ingredients)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-12 text-gray-400">
                            <i class="fas fa-seedling text-4xl mb-3 block opacity-50"></i>
                            No hay insumos registrados. Agrega tu primer ingrediente de cocina.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($ingredients as $ing): 
                        $stockClass = $ing['stock'] <= 0 ? 'text-red-500 bg-red-50 dark:bg-red-900/20' : 
                            ($ing['stock'] <= $ing['min_stock'] ? 'text-amber-600 bg-amber-50 dark:bg-amber-900/20' : 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20');
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition">
                        <td class="p-3 font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($ing['name']) ?></td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg text-xs font-bold">
                                <?= htmlspecialchars($ing['unit_abbr'] ?? 'und') ?>
                            </span>
                        </td>
                        <td class="p-3 text-center">
                            <span class="px-2 py-1 rounded-lg text-xs font-black <?= $stockClass ?>">
                                <?= number_format($ing['stock'], 2) ?> <?= htmlspecialchars($ing['unit_abbr'] ?? '') ?>
                            </span>
                        </td>
                        <td class="p-3 text-center font-bold text-gray-700 dark:text-gray-300">$<?= number_format($ing['cost_per_unit'], 4) ?></td>
                        <td class="p-3 text-center text-gray-500 text-xs"><?= htmlspecialchars($ing['supplier_name'] ?? '—') ?></td>
                        <td class="p-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="openRestock(<?= $ing['id'] ?>, '<?= htmlspecialchars($ing['name']) ?>')" class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 hover:bg-emerald-100 transition text-xs" title="Reabastecer">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button onclick="editInsumo(<?= htmlspecialchars(json_encode($ing)) ?>)" class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-600 hover:bg-blue-100 transition text-xs" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button onclick="deleteInsumo(<?= $ing['id'] ?>)" class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 transition text-xs" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Crear/Editar Insumo -->
<div id="insumoModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] hidden items-center justify-center p-4" style="display:none;">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-lg font-black text-gray-800 dark:text-white">Nuevo Insumo</h3>
            <button onclick="closeModal()" class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 hover:text-red-500 transition"><i class="fas fa-times"></i></button>
        </div>
        <form id="insumoForm" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="id" id="insumo_id">
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Nombre del Insumo *</label>
                <input type="text" name="name" id="insumo_name" required class="w-full p-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium" placeholder="Ej. Harina de Trigo">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Unidad de Medida</label>
                    <select name="unit_id" id="insumo_unit" class="w-full p-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium">
                        <option value="">Seleccionar...</option>
                        <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= $u['abbreviation'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Costo por Unidad ($)</label>
                    <input type="number" name="cost_per_unit" id="insumo_cost" step="0.0001" min="0" class="w-full p-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium" placeholder="0.0000">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Stock Actual</label>
                    <input type="number" name="stock" id="insumo_stock" step="0.01" min="0" class="w-full p-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium" placeholder="0">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Stock Mínimo</label>
                    <input type="number" name="min_stock" id="insumo_min" step="0.01" min="0" class="w-full p-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium" placeholder="0">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Proveedor (Opcional)</label>
                <select name="supplier_id" id="insumo_supplier" class="w-full p-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium">
                    <option value="">Sin proveedor</option>
                    <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full py-3 bg-brand-600 text-white font-bold rounded-xl hover:bg-brand-700 transition shadow-lg shadow-brand-500/30">
                <i class="fas fa-save mr-2"></i> Guardar Insumo
            </button>
        </form>
    </div>
</div>

<!-- Modal: Reabastecer -->
<div id="restockModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] hidden items-center justify-center p-4" style="display:none;">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <h3 class="text-lg font-black text-gray-800 dark:text-white mb-1">📦 Reabastecer</h3>
        <p id="restockName" class="text-sm text-gray-500 mb-4"></p>
        <input type="hidden" id="restock_id">
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Cantidad a Agregar</label>
                <input type="number" id="restock_qty" step="0.01" min="0.01" class="w-full p-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium" placeholder="0">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Nuevo Costo/Unidad (Opcional)</label>
                <input type="number" id="restock_cost" step="0.0001" min="0" class="w-full p-2.5 bg-gray-50 dark:bg-slate-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium" placeholder="Dejar vacío para mantener">
            </div>
            <div class="flex gap-2">
                <button onclick="closeRestock()" class="flex-1 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold rounded-xl">Cancelar</button>
                <button onclick="submitRestock()" class="flex-1 py-2.5 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition">Agregar Stock</button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
const CSRF = '<?= $_SESSION['csrf_token'] ?? '' ?>';

function openModal(data = null) {
    document.getElementById('modalTitle').textContent = data ? 'Editar Insumo' : 'Nuevo Insumo';
    document.getElementById('insumo_id').value = data?.id || '';
    document.getElementById('insumo_name').value = data?.name || '';
    document.getElementById('insumo_unit').value = data?.unit_id || '';
    document.getElementById('insumo_cost').value = data?.cost_per_unit || '';
    document.getElementById('insumo_stock').value = data?.stock || '';
    document.getElementById('insumo_min').value = data?.min_stock || '';
    document.getElementById('insumo_supplier').value = data?.supplier_id || '';
    document.getElementById('insumoModal').style.display = 'flex';
}
function closeModal() { document.getElementById('insumoModal').style.display = 'none'; }
function editInsumo(data) { openModal(data); }

document.getElementById('insumoForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    try {
        const res = await fetch(BASE + 'restaurant/save_insumo', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) { location.reload(); } else { alert(data.message); }
    } catch(err) { alert('Error de conexión'); }
});

function openRestock(id, name) {
    document.getElementById('restock_id').value = id;
    document.getElementById('restockName').textContent = name;
    document.getElementById('restock_qty').value = '';
    document.getElementById('restock_cost').value = '';
    document.getElementById('restockModal').style.display = 'flex';
}
function closeRestock() { document.getElementById('restockModal').style.display = 'none'; }

async function submitRestock() {
    const id = document.getElementById('restock_id').value;
    const qty = parseFloat(document.getElementById('restock_qty').value);
    const cost = document.getElementById('restock_cost').value;
    if (!qty || qty <= 0) { alert('Ingresa una cantidad válida'); return; }

    const body = { id: parseInt(id), quantity: qty };
    if (cost) body.cost_per_unit = parseFloat(cost);

    try {
        const res = await fetch(BASE + 'restaurant/restock_insumo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) { location.reload(); } else { alert(data.message); }
    } catch(err) { alert('Error de conexión'); }
}

async function deleteInsumo(id) {
    if (!confirm('¿Eliminar este insumo? Se eliminará también de las recetas que lo usen.')) return;
    try {
        const res = await fetch(BASE + 'restaurant/delete_insumo/' + id, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({})
        });
        const data = await res.json();
        if (data.success) { location.reload(); } else { alert(data.message); }
    } catch(err) { alert('Error de conexión'); }
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
