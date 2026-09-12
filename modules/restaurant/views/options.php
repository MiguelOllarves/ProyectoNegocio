<?php
require_once __DIR__ . '/../../../core/Settings.php';
$bcvRate = (float)Settings::getBcvRate();
$fmtUsd = function($n) { return number_format((float)$n, 2, ',', '.'); };
$fmtBs  = function($n) use ($bcvRate) { return number_format((float)$n * $bcvRate, 2, ',', '.'); };

include __DIR__ . '/../../../includes/header.php';
?>

<div class="max-w-5xl mx-auto pb-32 sm:pb-12">
    <div class="mb-6">
        <div class="flex items-center text-sm text-gray-500 mb-2">
            <a href="<?= BASE_URL ?>restaurant" class="hover:text-brand-600 transition-colors">Mis Platos</a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <span class="text-gray-800 dark:text-white font-medium">Opciones de <?= htmlspecialchars($dish['name']) ?></span>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center">
            <i class="fas fa-sliders-h text-brand-500 mr-3"></i>
            Configurar Opciones: <?= htmlspecialchars($dish['name']) ?>
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Define los grupos de opciones (ej: Contornos, Bebidas) y las opciones dentro de cada grupo.
        </p>
    </div>

    <!-- Resumen del plato -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 mb-6 flex items-center gap-4">
        <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-red-100 to-orange-100 dark:from-red-900/40 dark:to-orange-900/40 flex items-center justify-center text-2xl shrink-0">
            <?php if (!empty($dish['image'])): ?>
                <img src="<?= htmlspecialchars($dish['image']) ?>" class="w-full h-full object-cover rounded-xl" alt="">
            <?php else: ?>
                🍽️
            <?php endif; ?>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($dish['name']) ?></h3>
            <p class="text-sm text-gray-500">Precio base: $<?= $fmtUsd($dish['price']) ?></p>
        </div>
        <a href="<?= BASE_URL ?>restaurant/edit_dish_view/<?= $dish['id'] ?>" class="text-sm text-brand-600 hover:text-brand-700 font-bold">
            <i class="fas fa-edit mr-1"></i> Editar Plato
        </a>
    </div>

    <!-- Grupos de opciones -->
    <div id="groups-container">
        <?php if (empty($groups)): ?>
        <div id="empty-state" class="bg-white dark:bg-slate-800 border border-dashed border-gray-300 dark:border-gray-600 rounded-2xl p-12 text-center mb-6">
            <div class="w-16 h-16 mx-auto mb-4 bg-brand-50 dark:bg-brand-900/20 rounded-full flex items-center justify-center">
                <i class="fas fa-sliders-h text-2xl text-brand-400"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-700 dark:text-gray-200 mb-2">Sin opciones configuradas</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                Las opciones permiten al cliente personalizar su plato. Por ejemplo: elegir 2 contornos de una lista de 5.
            </p>
        </div>
        <?php endif; ?>

        <?php foreach ($groups as $group): ?>
        <div class="group-card bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 mb-4" data-group-id="<?= $group['id'] ?>">
            <div class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-gray-800 dark:text-white flex items-center">
                        <i class="fas fa-layer-group text-brand-500 mr-2"></i>
                        <?= htmlspecialchars($group['name']) ?>
                    </h4>
                    <p class="text-xs text-gray-400 mt-1">
                        Mín: <?= $group['min_selections'] ?> · Máx: <?= $group['max_selections'] ?>
                        · <?= count($group['options']) ?> opción(es)
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="toggleGroup(<?= $group['id'] ?>)" class="text-gray-400 hover:text-brand-500 transition-colors px-2 py-1">
                        <i class="fas fa-chevron-down group-toggle-icon"></i>
                    </button>
                    <button onclick="deleteGroup(<?= $group['id'] ?>, '<?= htmlspecialchars($group['name'], ENT_QUOTES) ?>')" class="text-gray-400 hover:text-red-500 transition-colors px-2 py-1">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
            
            <div class="group-options p-5">
                <!-- Opciones existentes -->
                <div class="options-list space-y-2 mb-4">
                    <?php foreach ($group['options'] as $opt): ?>
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-slate-700/40 border border-gray-200 dark:border-gray-600 rounded-xl px-4 py-3">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center text-brand-600 dark:text-brand-400 text-xs font-bold">
                                <i class="fas fa-check"></i>
                            </span>
                            <div>
                                <p class="text-sm font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($opt['product_name']) ?></p>
                                <p class="text-xs text-gray-400">
                                    <?php if ($opt['price_delta'] > 0): ?>
                                        <span class="text-green-600 dark:text-green-400">+$<?= $fmtUsd($opt['price_delta']) ?></span>
                                    <?php elseif ($opt['price_delta'] < 0): ?>
                                        <span class="text-red-500">-$<?= $fmtUsd(abs($opt['price_delta'])) ?></span>
                                    <?php else: ?>
                                        Sin costo adicional
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <button onclick="deleteOption(<?= $opt['id'] ?>)" class="text-gray-400 hover:text-red-500 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Formulario para agregar opción -->
                <div class="flex flex-col sm:flex-row gap-3 items-end">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-gray-500 mb-1">Producto / Ingrediente</label>
                        <select id="opt-product-<?= $group['id'] ?>" class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm text-gray-800 dark:text-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($products as $prod): ?>
                                <option value="<?= $prod['id'] ?>"><?= htmlspecialchars($prod['name']) ?> ($<?= $fmtUsd($prod['price']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-full sm:w-32">
                        <label class="block text-xs font-bold text-gray-500 mb-1">Delta precio ($)</label>
                        <input type="number" step="0.01" id="opt-delta-<?= $group['id'] ?>" value="0" class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm text-gray-800 dark:text-white px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 text-center">
                    </div>
                    <button onclick="addOption(<?= $group['id'] ?>)" class="w-full sm:w-auto px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white rounded-xl text-sm font-bold transition-colors">
                        <i class="fas fa-plus mr-1"></i> Agregar
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Botón para crear grupo -->
    <div class="mt-6">
        <button onclick="openCreateGroup()" class="w-full py-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl text-gray-500 dark:text-gray-400 hover:border-brand-500 hover:text-brand-500 transition-colors font-bold text-sm">
            <i class="fas fa-plus mr-2"></i> Agregar Grupo de Opciones
        </button>
    </div>
</div>

<!-- Modal: Crear Grupo -->
<div id="modal-create-group" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm hidden">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-2xl border border-gray-100 dark:border-gray-700">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center">
            <i class="fas fa-layer-group text-brand-500 mr-3"></i> Nuevo Grupo de Opciones
        </h3>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Nombre del grupo <span class="text-red-500">*</span></label>
                <input type="text" id="group-name" placeholder="Ej: Contornos, Bebidas, Salsas..." class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-slate-800 text-gray-800 dark:text-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Mínimo a elegir</label>
                    <input type="number" id="group-min" min="0" value="0" class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-slate-800 text-gray-800 dark:text-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 text-center">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Máximo a elegir</label>
                    <input type="number" id="group-max" min="1" value="1" class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-slate-800 text-gray-800 dark:text-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 text-center">
                </div>
            </div>
            <p class="text-xs text-gray-400">Ejemplo: "Contornos" con mín=2, máx=2 obliga al cliente a elegir exactamente 2 contornos.</p>
        </div>

        <div class="flex space-x-3 mt-6">
            <button onclick="closeCreateGroup()" class="w-1/3 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-bold transition-colors">
                Cancelar
            </button>
            <button onclick="createGroup()" class="flex-1 py-3 bg-brand-600 hover:bg-brand-500 text-white rounded-xl font-bold shadow-lg transition-colors">
                <i class="fas fa-save mr-2"></i> Crear Grupo
            </button>
        </div>
    </div>
</div>

<script>
const DISH_ID = <?= $dish['id'] ?>;
const BASE_URL_VAL = '<?= BASE_URL ?>';

function openCreateGroup() {
    document.getElementById('modal-create-group').classList.remove('hidden');
    document.getElementById('group-name').focus();
}

function closeCreateGroup() {
    document.getElementById('modal-create-group').classList.add('hidden');
    document.getElementById('group-name').value = '';
    document.getElementById('group-min').value = '0';
    document.getElementById('group-max').value = '1';
}

async function createGroup() {
    const name = document.getElementById('group-name').value.trim();
    const minSel = parseInt(document.getElementById('group-min').value) || 0;
    const maxSel = parseInt(document.getElementById('group-max').value) || 1;

    if (!name) {
        Swal.fire('Atención', 'Ingresa el nombre del grupo', 'warning');
        return;
    }

    try {
        const res = await fetch(BASE_URL_VAL + 'restaurant/save_option_group/' + DISH_ID, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify({ name, min_selections: minSel, max_selections: maxSel })
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ title: '¡Grupo creado!', icon: 'success', timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Error de red', 'error');
    }
}

async function addOption(groupId) {
    const productSelect = document.getElementById('opt-product-' + groupId);
    const deltaInput = document.getElementById('opt-delta-' + groupId);
    const productId = productSelect.value;
    const delta = parseFloat(deltaInput.value) || 0;

    if (!productId) {
        Swal.fire('Atención', 'Selecciona un producto', 'warning');
        return;
    }

    try {
        const res = await fetch(BASE_URL_VAL + 'restaurant/save_option/' + groupId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify({ product_id: productId, price_delta: delta })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Error de red', 'error');
    }
}

async function deleteGroup(groupId, name) {
    const result = await Swal.fire({
        title: '¿Eliminar grupo "' + name + '"?',
        text: 'Se eliminarán todas las opciones dentro de este grupo.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    if (!result.isConfirmed) return;

    try {
        const res = await fetch(BASE_URL_VAL + 'restaurant/delete_option_group/' + groupId, {
            method: 'POST',
            headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' }
        });
        const data = await res.json();
        if (data.success) {
            document.querySelector(`.group-card[data-group-id="${groupId}"]`)?.remove();
            if (!document.querySelector('.group-card')) location.reload();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (e) {
        Swal.fire('Error', 'Error de red', 'error');
    }
}

async function deleteOption(optionId) {
    try {
        const res = await fetch(BASE_URL_VAL + 'restaurant/delete_option/' + optionId, {
            method: 'POST',
            headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '' }
        });
        const data = await res.json();
        if (data.success) location.reload();
        else Swal.fire('Error', data.message, 'error');
    } catch (e) {
        Swal.fire('Error', 'Error de red', 'error');
    }
}

function toggleGroup(groupId) {
    const card = document.querySelector(`.group-card[data-group-id="${groupId}"]`);
    const optionsDiv = card?.querySelector('.group-options');
    const icon = card?.querySelector('.group-toggle-icon');
    if (optionsDiv) {
        const isHidden = optionsDiv.style.display === 'none';
        optionsDiv.style.display = isHidden ? '' : 'none';
        if (icon) icon.className = isHidden ? 'fas fa-chevron-up group-toggle-icon' : 'fas fa-chevron-down group-toggle-icon';
    }
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>
