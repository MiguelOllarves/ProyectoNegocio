<?php
require_once __DIR__ . '/../../../core/Settings.php';
$bcvRate = (float)Settings::getBcvRate();
$isEdit = ($mode ?? 'create') === 'edit';
$actionUrl = $isEdit ? BASE_URL . 'restaurant/update/' . $product['id'] : BASE_URL . 'restaurant/create';
$dishPrice = (float)($product['price'] ?? 0);
include __DIR__ . '/../../../includes/header.php';
?>

<script>
    window.ingredientsData = <?= json_encode($ingredients ?? []) ?>;
    window.unitsData = <?= json_encode($units ?? []) ?>;
    window.recipeInitial = <?= json_encode(array_map(function($r) {
        return [
            'ingredient_id' => $r['ingredient_id'],
            'quantity' => (float)$r['quantity'],
            'unit_id' => $r['unit_id'] !== null ? (int)$r['unit_id'] : null
        ];
    }, $recipeItems ?? [])) ?>;
    window.dishPriceInputId = "price";
    window.bcvRate = <?= json_encode($bcvRate) ?>;

    // Formato venezolano: 1.234,56
    function fmt(n, decimals = 2) {
        return (parseFloat(n) || 0).toFixed(decimals).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
</script>

<div class="max-w-5xl mx-auto pb-32 sm:pb-12">
    <div class="mb-6">
        <div class="flex items-center text-sm text-gray-500 mb-2">
            <a href="<?= BASE_URL ?>restaurant" class="hover:text-brand-600 transition-colors">Mis Platos</a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <span class="text-gray-800 dark:text-white font-medium"><?= $isEdit ? 'Editar Plato' : 'Nuevo Plato' ?></span>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center">
            <i class="fas fa-utensils text-red-500 mr-3"></i>
            <?= $isEdit ? 'Editar: ' . htmlspecialchars($product['name']) : 'Crear un Plato Nuevo' ?>
        </h2>
    </div>

    <form action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data" id="dish-form" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <!-- Paso 1: Datos básicos -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 sm:p-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-2 h-full bg-red-500"></div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-6 flex items-center flex-wrap gap-y-2">
                <span class="bg-red-100 text-red-600 rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm shrink-0">1</span>
                ¿Qué vas a vender?
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Nombre del Plato <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($product['name'] ?? '') ?>" placeholder="Ej: Hamburguesa de la casa" class="w-full rounded-xl border border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-slate-900 dark:text-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">% Ganancia</label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" name="profit_margin" id="profit_margin" value="<?= htmlspecialchars($product['profit_margin'] ?? '50') ?>" placeholder="50" class="w-full rounded-xl border border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-slate-900 dark:text-white px-4 pr-9 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all shadow-sm">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 font-bold">%</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Minutos de preparación</label>
                    <input type="number" step="1" min="0" name="prep_time" value="<?= htmlspecialchars($product['prep_time'] ?? '') ?>" placeholder="Ej: 15" class="w-full rounded-xl border border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-slate-900 dark:text-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all shadow-sm">
                </div>
                <div class="lg:col-span-4">
                    <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Imagen del Plato (Opcional)</label>
                    <input type="file" name="image" accept="image/*" class="w-full rounded-xl border-2 border-dashed border-slate-300 dark:border-gray-500 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-white px-4 py-4 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all text-sm file:mr-6 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-brand-100 file:text-brand-700 hover:file:bg-brand-200 cursor-pointer shadow-sm">
                    <?php if (!empty($product['image'])): ?>
                        <p class='text-xs mt-2 text-gray-500'><i class="fas fa-check-circle text-green-500 mr-1"></i> Ya tiene imagen asignada.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Precio automático -->
            <div class="mt-5 bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800 rounded-2xl p-5">
                <div class="mb-4">
                    <p class="text-sm font-bold uppercase tracking-wider text-green-700 dark:text-green-300 mb-1">
                        <i class="fas fa-magic mr-1"></i> Precio de Venta
                    </p>
                    <p class="text-xs text-green-600/80 dark:text-green-400/70 leading-tight">Por defecto se calcula solo sumando tus ingredientes + ganancia. ¡Pero si quieres puedes borrarlo y escribir tu propio precio directamente!</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Precio en USD -->
                    <div class="relative w-full">
                        <label class="block text-xs font-bold text-green-700 dark:text-green-300 mb-1">Dólares (USD)</label>
                        <span class="absolute bottom-3 left-0 flex items-center pl-4 text-green-800 font-bold">$</span>
                        <input type="number" step="0.01" min="0" name="price" id="price" required value="<?= htmlspecialchars($dishPrice ?: '') ?>" placeholder="Ej: 8.75" class="w-full text-xl font-black text-green-900 dark:text-green-300 rounded-xl border-2 border-green-400 bg-white dark:bg-slate-900 pl-8 pr-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 transition-all shadow-sm">
                    </div>
                    <!-- Precio en BS (Solo visual/calculadora) -->
                    <div class="relative w-full">
                        <label class="block text-xs font-bold text-green-700 dark:text-green-300 mb-1">Bolívares (Bs)</label>
                        <span class="absolute bottom-3 left-0 flex items-center pl-4 text-green-800 font-bold">Bs</span>
                        <input type="number" step="0.01" min="0" id="price_bs_input" placeholder="0.00" class="w-full text-xl font-black text-green-900 dark:text-green-300 rounded-xl border-2 border-green-400 bg-white dark:bg-slate-900 pl-10 pr-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 transition-all shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Paso 2: Ingredientes -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden relative">
            <div class="absolute top-0 left-0 w-2 h-full bg-orange-500"></div>
            <div class="px-5 sm:px-8 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap justify-between items-center gap-3 bg-gray-50/50 dark:bg-slate-700/30">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center">
                    <span class="bg-orange-100 text-orange-600 rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm">2</span>
                    ¿Qué lleva el plato?
                </h3>
                <button type="button" id="btn-add-row" class="btn-primary text-xs py-2 min-h-0"><i class="fas fa-plus mr-1"></i> Añadir Ingrediente</button>
            </div>
            <div class="p-5 sm:p-8">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    <i class="fas fa-info-circle text-orange-400 mr-1"></i>
                    Dime cuánto de cada ingrediente usa <b>una porción</b> del plato. Ej: 150 Gramos de Carne Molida.
                </p>

                <div id="recipe-rows" class="space-y-3"></div>
                <div id="empty-msg" class="text-sm text-slate-400 p-8 text-center border-2 border-dashed border-slate-200 dark:border-gray-600 rounded-xl bg-slate-50 dark:bg-slate-900/40 hidden">
                    <i class="fas fa-carrot text-3xl text-slate-300 mb-2 block"></i>
                    Añade los ingredientes que lleva el plato.
                </div>

                <!-- Resumen en vivo -->
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="bg-gray-50 dark:bg-slate-700/40 border border-gray-200 dark:border-gray-600 rounded-xl p-3 text-center relative group">
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wide mb-1">Costo por plato <i class="fas fa-info-circle text-gray-300 cursor-help ml-1" title="Suma de los costos de los ingredientes. Si es $0.00 es porque no has agregado ingredientes."></i></p>
                        <p class="text-lg font-black text-brand-600">$<span id="live-cost">0,00</span></p>
                        <p class="text-[10px] font-semibold text-gray-400">Bs. <span id="live-cost-bs">0,00</span></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-700/40 border border-gray-200 dark:border-gray-600 rounded-xl p-3 text-center">
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wide mb-1">Ganancia por plato</p>
                        <p class="text-lg font-black"><span id="live-profit" class="text-gray-400">—</span></p>
                        <p class="text-[10px] font-semibold text-gray-400">Bs. <span id="live-profit-bs">—</span></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-700/40 border border-gray-200 dark:border-gray-600 rounded-xl p-3 text-center">
                        <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wide mb-1">Puedo preparar</p>
                        <p class="text-lg font-black"><span id="live-servings" class="text-gray-400">—</span></p>
                    </div>
                </div>
                
                <p class="text-xs text-gray-400 text-center mt-3" id="cost-help-text">
                    <i class="fas fa-lightbulb text-yellow-400 mr-1"></i> Si el Costo te sale en $0,00 y la Ganancia es igual al Precio, es porque <b>aún no has agregado ingredientes</b> arriba.
                </p>

                <?php if (empty($ingredients)): ?>
                <div class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 text-xs text-yellow-800 dark:text-yellow-200">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Aún no tienes ingredientes en el <b>Inventario</b>. Regístralos primero allí para poder usarlos aquí.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 sm:border-t sm:border-slate-200 dark:border-gray-700 sm:pt-6">
            <a href="<?= BASE_URL ?>restaurant" class="w-full sm:w-auto px-6 py-4 sm:py-3 bg-slate-100 sm:bg-white dark:bg-slate-800 border-transparent sm:border-slate-300 dark:border-gray-600 border rounded-xl text-slate-700 dark:text-gray-200 hover:bg-slate-200 font-bold text-center transition-all">Cancelar</a>
            <button type="submit" class="w-full sm:w-auto px-8 py-4 sm:py-3 bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-500 hover:to-brand-400 text-white rounded-xl font-bold shadow-lg shadow-brand-500/30 transition-all flex justify-center items-center hover:-translate-y-0.5">
                <i class="fas fa-save mr-2 text-xl"></i> <?= $isEdit ? 'Guardar Cambios' : 'Crear Plato' ?>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const rowsContainer = document.getElementById("recipe-rows");
    const emptyMsg = document.getElementById("empty-msg");
    const priceInput = document.getElementById("price");
    const marginInput = document.getElementById("profit_margin");
    let currentCost = 0;
    let priceManuallyEdited = false;

    const unitsByType = {};
    unitsData.forEach(u => {
        if (!unitsByType[u.base_type]) unitsByType[u.base_type] = [];
        unitsByType[u.base_type].push(u);
    });

    function updateEmptyMsg() {
        emptyMsg.classList.toggle("hidden", rowsContainer.children.length > 0);
    }

    function getRowData(row) {
        const sel = row.querySelector(".ing-select");
        const qty = parseFloat(row.querySelector(".ing-qty").value) || 0;
        const unitSel = row.querySelector(".ing-unit");
        const warningIcon = row.querySelector(".unit-warning");
        if (warningIcon) warningIcon.classList.add("hidden");
        
        const ing = ingredientsData.find(i => i.id == sel.value);
        if (!ing || qty <= 0) return null;

        let qtySale = qty;
        let qtyBase = qty;
        if (unitSel.value) {
            const fromUnit = unitsData.find(u => u.id == unitSel.value);
            const toSale = unitsData.find(u => u.id == ing.sale_unit_id);
            if (fromUnit) {
                if (toSale && fromUnit.base_type === toSale.base_type && parseFloat(toSale.conversion_to_base) > 0) {
                    qtyBase = qty * parseFloat(fromUnit.conversion_to_base);
                    qtySale = qtyBase / parseFloat(toSale.conversion_to_base);
                } else if (!toSale || toSale.id == fromUnit.id) {
                    qtyBase = qty * parseFloat(fromUnit.conversion_to_base);
                    qtySale = qty;
                } else {
                    // Si son incompatibles (Ej: Gramos vs Bulto), intentamos conversión de 2 pasos
                    // usando el contenido interno del producto (Ej: Bulto trae 20 Kg)
                    const containedUnit = unitsData.find(u => u.id == ing.contained_unit_id);
                    if (containedUnit && fromUnit.base_type === containedUnit.base_type && parseFloat(containedUnit.conversion_to_base) > 0) {
                        // 1. Convertir de la unidad elegida (Gramos) a la unidad contenida (Kg)
                        qtyBase = qty * parseFloat(fromUnit.conversion_to_base);
                        let qtyInContained = qtyBase / parseFloat(containedUnit.conversion_to_base);
                        // 2. Dividir por la cantidad que trae el bulto (20 Kg)
                        let contentPerPurchase = parseFloat(ing.content_per_purchase) || 1;
                        qtySale = qtyInContained / contentPerPurchase;
                    } else if (parseFloat(ing.content_per_purchase) > 1 && (fromUnit.base_type === 'peso' || fromUnit.base_type === 'volumen')) {
                        // MAGIA: Si el usuario guardó un Bulto/Caja con "Trae 20", y usa Gramos, 
                        // pero la unidad contenida guardada fue inválida (ej. Bulto trae Bultos),
                        // asumimos inteligentemente que esos "20" son Kg o Litros.
                        let assumedContentInBase = 0;
                        if (fromUnit.base_type === 'peso') {
                            assumedContentInBase = parseFloat(ing.content_per_purchase) * 1000; // Asumir Kg -> g
                        } else if (fromUnit.base_type === 'volumen') {
                            assumedContentInBase = parseFloat(ing.content_per_purchase) * 1000; // Asumir L -> ml
                        }
                        
                        qtyBase = qty * parseFloat(fromUnit.conversion_to_base);
                        let fractionOfContainer = qtyBase / assumedContentInBase;
                        qtySale = fractionOfContainer;
                        if (warningIcon) warningIcon.classList.add("hidden");
                    } else {
                        // Tipos de medida definitivamente incompatibles y sin contenido interno definido
                        qtySale = 0; 
                        if (warningIcon) warningIcon.classList.remove("hidden");
                    }
                }
            }
        }
        return { ing, qtySale, qtyBase };
    }

    function updateSummary() {
        let totalCost = 0;
        let servings = Infinity;
        let hasAny = false;

        rowsContainer.querySelectorAll(".recipe-row").forEach(row => {
            const d = getRowData(row);
            const costSpan = row.querySelector(".row-cost");
            if (!d) {
                if (costSpan) costSpan.innerText = "$0.00";
                return;
            }
            hasAny = true;
            let rowCost = d.qtyBase * parseFloat(d.ing.unit_cost || 0);
            totalCost += rowCost;
            if (costSpan) costSpan.innerText = "$" + fmt(rowCost);
            
            if (d.qtyBase > 0) {
                servings = Math.min(servings, Math.floor((parseFloat(d.ing.stock) || 0) / d.qtyBase));
            }
        });

        totalCost = Math.round(totalCost * 10000) / 10000;
        currentCost = totalCost;
        document.getElementById("live-cost").innerText = fmt(totalCost);
        document.getElementById("live-cost-bs").innerText = fmt(totalCost * bcvRate);

        // PRECIO AUTOMÁTICO: costo + % de ganancia
        const priceBsInput = document.getElementById("price_bs_input");
        if (!priceManuallyEdited && document.activeElement !== priceInput && document.activeElement !== priceBsInput) {
            const margin = parseFloat(marginInput.value) || 0;
            const newPrice = totalCost * (1 + margin / 100);
            priceInput.value = newPrice > 0 ? newPrice.toFixed(2) : "";
            priceBsInput.value = newPrice > 0 ? (newPrice * bcvRate).toFixed(2) : "";
        }

        const price = parseFloat(priceInput.value) || 0;
        
        // Ensure Bs input stays in sync if not active
        if (document.activeElement !== priceBsInput && document.activeElement !== priceInput) {
            priceBsInput.value = price > 0 ? (price * bcvRate).toFixed(2) : "";
        }

        const profitEl = document.getElementById("live-profit");
        const profitBsEl = document.getElementById("live-profit-bs");
        if (!hasAny) {
            profitEl.innerText = "—";
            profitEl.className = "text-gray-400";
            profitBsEl.innerText = "—";
        } else {
            const profit = price - totalCost;
            profitEl.innerText = "$" + fmt(profit);
            profitEl.className = profit >= 0 ? "text-green-600" : "text-red-500";
            profitBsEl.innerText = fmt(profit * bcvRate);
        }

        const servEl = document.getElementById("live-servings");
        if (!hasAny || servings === Infinity) {
            servEl.innerText = "—";
            servEl.className = "text-gray-400";
        } else {
            servEl.innerText = Math.max(0, servings);
            servEl.className = Math.max(0, servings) > 0 ? "text-green-600" : "text-red-500";
        }
    }

    function addRow(data = {}) {
        const row = document.createElement("div");
        row.className = "recipe-row flex flex-col sm:flex-row gap-3 items-stretch sm:items-center animate-fade-in-up bg-slate-50 dark:bg-slate-900/40 p-3 rounded-xl border border-slate-200 dark:border-gray-700";

        let ingOptions = '<option value="">-- Elige un ingrediente --</option>';
        ingredientsData.forEach(i => {
            const selected = data.ingredient_id == i.id ? "selected" : "";
            ingOptions += `<option value="${i.id}" ${selected}>${i.name.replace(/</g, '&lt;')}</option>`;
        });

        row.innerHTML = `
            <div class="flex-[3]">
                <select name="ingredient_id[]" class="ing-select w-full rounded-lg border border-slate-200 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm text-gray-800 dark:text-gray-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer">${ingOptions}</select>
            </div>
            <div class="flex-[1]">
                <input type="number" name="quantity[]" step="0.001" min="0" placeholder="Cant." value="${data.quantity ?? ''}" class="ing-qty w-full rounded-lg border border-slate-200 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm text-gray-800 dark:text-gray-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
            </div>
            <div class="flex-[1] relative">
                <select name="unit_id[]" class="ing-unit w-full rounded-lg border border-slate-200 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm text-gray-800 dark:text-gray-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer"><option value="">Unidad...</option></select>
                <i class="unit-warning hidden fas fa-exclamation-triangle text-red-500 absolute -top-2 -right-2 bg-white rounded-full p-0.5 shadow-sm" title="Error de medida: Incompatible con inventario."></i>
            </div>
            <div class="flex items-center justify-center min-w-[80px]">
                <span class="row-cost text-sm font-bold text-gray-500 dark:text-gray-400">$0.00</span>
            </div>
            <button type="button" class="remove-row text-slate-400 hover:text-red-500 p-2 transition-colors shrink-0" title="Quitar"><i class="fas fa-trash-alt"></i></button>
        `;

        const unitSel = row.querySelector(".ing-unit");
        const ingSel = row.querySelector(".ing-select");

        function populateUnits() {
            const ing = ingredientsData.find(i => i.id == ingSel.value);
            unitSel.innerHTML = '<option value="">Unidad...</option>';
            if (ing) {
                const families = [
                    ['peso',    'Peso'],
                    ['volumen', 'Volumen']
                ];
                // Solo Gramo (1), Kilogramo (4), Litro (9), Mililitro (2)
                const culinaryUnitIds = [1, 2, 4, 9]; 
                let defaultSet = false;
                families.forEach(([family, label]) => {
                    const list = (unitsByType[family] || []).filter(u => culinaryUnitIds.includes(parseInt(u.id)));
                    if (!list.length) return;
                    let opts = '';
                    list.forEach(u => {
                        const selected = (data.unit_id && data.unit_id == u.id) ? "selected" : "";
                        if (selected) defaultSet = true;
                        opts += `<option value="${u.id}" ${selected}>${u.name} (${u.abbreviation})</option>`;
                    });
                    unitSel.innerHTML += `<optgroup label="${label}">${opts}</optgroup>`;
                });
                
                if (!defaultSet && !data.unit_id && ing.sale_unit_id && culinaryUnitIds.includes(parseInt(ing.sale_unit_id))) {
                    unitSel.value = ing.sale_unit_id;
                }
            }
            updateSummary();
        }

        ingSel.addEventListener("change", () => { data.unit_id = null; populateUnits(); });
        unitSel.addEventListener("change", updateSummary);
        row.querySelector(".ing-qty").addEventListener("input", updateSummary);
        row.querySelector(".remove-row").addEventListener("click", () => { row.remove(); updateEmptyMsg(); updateSummary(); });

        rowsContainer.appendChild(row);
        populateUnits();
        updateEmptyMsg();
    }

    document.getElementById("btn-add-row").addEventListener("click", () => addRow());

    // Si el usuario cambia el % de ganancia, el precio se recalcula solo
    marginInput.addEventListener("input", () => {
        priceManuallyEdited = false; // reset manual flag to allow auto recalc
        updateSummary();
    });

    // Si el usuario escribe el precio a mano (USD), recalculamos BS y Ganancia
    priceInput.addEventListener("input", function() {
        priceManuallyEdited = true;
        const price = parseFloat(priceInput.value) || 0;
        document.getElementById("price_bs_input").value = (price * bcvRate).toFixed(2);
        
        if (currentCost > 0 && price > 0) {
            marginInput.value = (((price - currentCost) / currentCost) * 100).toFixed(2);
        }
        updateSummary();
    });

    // Si el usuario escribe el precio en Bs a mano, recalculamos USD y Ganancia
    const priceBsInput = document.getElementById("price_bs_input");
    priceBsInput.addEventListener("input", function() {
        priceManuallyEdited = true;
        const priceBs = parseFloat(priceBsInput.value) || 0;
        const priceUsd = priceBs / bcvRate;
        priceInput.value = priceUsd.toFixed(2);
        
        if (currentCost > 0 && priceUsd > 0) {
            marginInput.value = (((priceUsd - currentCost) / currentCost) * 100).toFixed(2);
        }
        updateSummary();
    });


    (window.recipeInitial || []).forEach(item => addRow(item));
    if (!(window.recipeInitial || []).length) addRow();

    document.getElementById("dish-form").addEventListener("submit", function(e) {
        let valid = true;
        rowsContainer.querySelectorAll(".recipe-row").forEach(row => {
            const ingId = row.querySelector(".ing-select").value;
            const qty = parseFloat(row.querySelector(".ing-qty").value) || 0;
            if (!ingId && qty <= 0) { row.remove(); return; } // fila vacía: se ignora
            if (!ingId || qty <= 0) valid = false;
        });
        
        if (!valid) {
            e.preventDefault();
            Swal.fire("Atención", "Hay ingredientes sin cantidad o filas incompletas. Revísalas antes de guardar.", "warning");
            return;
        }

        if (!this.dataset.confirmed) {
            e.preventDefault();
            Swal.fire({
                title: '¿Guardar plato?',
                text: "Verifica que el precio y los ingredientes sean correctos.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#ef4444',
                confirmButtonText: '<i class="fas fa-check mr-1"></i> Sí, confirmar',
                cancelButtonText: 'Revisar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.dataset.confirmed = "1";
                    this.submit();
                }
            });
        }
    });

    updateSummary();
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
