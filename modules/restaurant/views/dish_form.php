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

    function fmt(n, decimals = 2) {
        return (parseFloat(n) || 0).toFixed(decimals).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
</script>

<div class="max-w-5xl mx-auto pb-32 sm:pb-12">
    <div class="mb-6">
        <div class="flex items-center text-sm text-gray-500 mb-2">
            <a href="<?= BASE_URL ?>restaurant" class="hover:text-brand-600 transition-colors">Mis Platos</a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <span class="text-gray-800 dark:text-white font-medium"><?= $isEdit ? 'Editar Plato / Combo' : 'Nuevo Plato / Combo' ?></span>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center">
            <i class="fas fa-utensils text-brand-500 mr-3"></i>
            <?= $isEdit ? 'Editar: ' . htmlspecialchars($product['name']) : 'Crear un Plato o Combo Nuevo' ?>
        </h2>
    </div>

    <form action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data" id="dish-form" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <!-- Paso 1: Datos básicos -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 sm:p-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-2 h-full bg-brand-500"></div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-6 flex items-center flex-wrap gap-y-2">
                <span class="bg-brand-100 text-brand-600 rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm shrink-0">1</span>
                ¿Qué vas a vender?
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Nombre del Plato o Combo <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($product['name'] ?? '') ?>" placeholder="Ej: Hamburguesa de la casa con Refresco" class="w-full rounded-xl border border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-slate-900 dark:text-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Minutos de preparación</label>
                    <input type="number" step="1" min="0" name="prep_time" value="<?= htmlspecialchars($product['prep_time'] ?? '') ?>" placeholder="Ej: 15 (minutos)" class="w-full rounded-xl border border-slate-200 dark:border-gray-600 bg-slate-50 dark:bg-slate-900 dark:text-white px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all shadow-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2">Imagen referencial (Opcional)</label>
                    <input type="file" name="image" accept="image/*" class="w-full rounded-xl border-2 border-dashed border-slate-300 dark:border-gray-500 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-white px-4 py-4 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all text-sm file:mr-6 file:py-3 file:px-6 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-brand-100 file:text-brand-700 hover:file:bg-brand-200 cursor-pointer shadow-sm">
                    <?php if (!empty($product['image'])): ?>
                        <p class='text-xs mt-2 text-gray-500'><i class="fas fa-check-circle text-green-500 mr-1"></i> Ya tiene imagen asignada.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Paso 2: Ingredientes -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden relative">
            <div class="absolute top-0 left-0 w-2 h-full bg-orange-500"></div>
            <div class="px-5 sm:px-8 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-wrap justify-between items-center gap-3 bg-gray-50/50 dark:bg-slate-700/30">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center">
                    <span class="bg-orange-100 text-orange-600 rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm">2</span>
                    ¿Qué lleva por dentro? (Arma tu receta o combo)
                </h3>
            </div>
            <div class="p-5 sm:p-8">
                <div class="text-sm text-slate-600 dark:text-gray-400 mb-6 bg-orange-50 dark:bg-orange-900/20 p-4 rounded-xl border border-orange-100 dark:border-orange-800/30 leading-relaxed">
                    <i class="fas fa-info-circle text-orange-400 mr-1"></i>
                    Añade ingredientes pesables (Ej: <b>200 gramos</b> de Carne) o productos directos ya listos del inventario (Ej: <b>1 Unidad</b> de Refresco). El sistema calculará el costo juntando ambos mundos.
                </div>

                <div id="recipe-rows" class="space-y-3"></div>
                
                <div class="mt-4">
                    <button type="button" id="btn-add-row" class="btn-primary text-sm py-2 px-4 shadow-sm hover:-translate-y-0.5"><i class="fas fa-plus mr-1"></i> Añadir Ingrediente o Producto</button>
                </div>
            </div>
        </div>

        <!-- Paso 3: Costos y Precio Final -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 sm:p-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-2 h-full bg-green-500"></div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-6 flex items-center flex-wrap gap-y-2">
                <span class="bg-green-100 text-green-600 rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm shrink-0">3</span>
                ¿A cuánto lo vas a vender? (Costos y Ganancias)
            </h3>
            
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4" id="cost-help-text">
                <i class="fas fa-lightbulb text-yellow-400 mr-1"></i> Te muestro un resumen interno del costo según lo que pusiste arriba. Configura tu margen para sacar el precio.
            </p>

            <!-- Resumen en vivo -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
                <!-- Tarjeta de Costo Matematico -->
                <div class="bg-slate-50 dark:bg-slate-700/40 border border-slate-200 dark:border-slate-600 rounded-xl p-4 relative group flex flex-col justify-center shadow-inner">
                    <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wide mb-1">Me cuesta prepararlo</p>
                    <p class="text-2xl font-black text-slate-700 dark:text-white">$<span id="live-cost">0,00</span></p>
                    <p class="text-[11px] font-semibold text-slate-400">Equivale a: Bs. <span id="live-cost-bs">0,00</span></p>
                </div>
                
                <!-- Tarjeta de Ganancia Neta -->
                <div class="bg-slate-50 dark:bg-slate-700/40 border border-slate-200 dark:border-slate-600 rounded-xl p-4 flex flex-col justify-center">
                    <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wide mb-1">Mi ganancia neta</p>
                    <p class="text-2xl font-black text-slate-700 dark:text-white"><span id="live-profit" class="text-gray-400">—</span></p>
                    <p class="text-[11px] font-semibold text-slate-400">Equivale a: Bs. <span id="live-profit-bs">—</span></p>
                </div>
                
                <!-- Tarjeta de Stock Alcanzable -->
                <div class="bg-slate-50 dark:bg-slate-700/40 border border-slate-200 dark:border-slate-600 rounded-xl p-4 flex flex-col justify-center lg:col-span-1 sm:col-span-2">
                    <p class="text-[10px] uppercase font-bold text-slate-500 tracking-wide mb-1">Puedo preparar hoy</p>
                    <p class="text-2xl font-black text-slate-700 dark:text-white"><span id="live-servings" class="text-gray-400">—</span> <span class="text-sm font-bold opacity-50">Platos</span></p>
                    <p class="text-[10px] font-medium text-slate-400 leading-tight mt-1">Límite según inventario.</p>
                </div>
            </div>

            <!-- Calculadora interactiva -->
            <div class="border-t border-dashed border-gray-200 dark:border-gray-700 pt-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 items-end">
                    <!-- Margen de Ganancia -->
                    <div class="w-full">
                        <label class="block text-sm font-bold text-slate-700 dark:text-gray-300 mb-2 whitespace-nowrap">% de Ganancia Deseada</label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" name="profit_margin" id="profit_margin" value="<?= htmlspecialchars($product['profit_margin'] ?? '50') ?>" class="w-full text-lg rounded-xl border border-slate-200 bg-slate-50 dark:bg-slate-900 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all font-bold text-brand-700">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 font-bold">%</span>
                        </div>
                    </div>
                    
                    <!-- Flecha decorativa -->
                    <div class="hidden sm:flex justify-center items-center pb-4 text-green-300">
                        <i class="fas fa-arrow-right text-3xl"></i>
                    </div>

                    <!-- Precio Final -->
                    <div class="w-full sm:col-span-2 lg:col-span-1">
                        <label class="block text-sm font-bold text-green-800 dark:text-green-400 mb-2 truncate">Precio Final (USD)</label>
                        <div class="relative w-full">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-green-800 font-bold text-xl">$</span>
                            <input type="number" step="0.01" min="0" name="price" id="price" required value="<?= htmlspecialchars($dishPrice ?: '') ?>" placeholder="Ej: 8.75" class="w-full text-2xl font-black text-green-900 dark:text-green-300 rounded-xl border-2 border-green-400 bg-green-50 dark:bg-slate-900 pl-10 pr-4 py-3 focus:outline-none focus:ring-4 focus:ring-green-100 transition-all shadow-sm">
                        </div>
                        <div class="mt-2 text-[11px] font-bold text-green-700 opacity-80 pl-2">
                            Equivale a: <span id="ves_sale_price">Bs. 0,00</span> 🇻🇪
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pb-12">
            <a href="<?= BASE_URL ?>restaurant" class="w-full sm:w-auto px-6 py-4 sm:py-3 bg-slate-100 sm:bg-white dark:bg-slate-800 border border-slate-300 dark:border-gray-600 rounded-xl text-slate-700 dark:text-gray-200 hover:bg-slate-200 font-bold text-center transition-all">Cancelar</a>
            <button type="submit" class="w-full sm:w-auto px-8 py-4 sm:py-3 bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-500 hover:to-brand-400 text-white rounded-xl font-bold shadow-lg shadow-brand-500/30 transition-all flex justify-center items-center hover:-translate-y-0.5">
                <i class="fas fa-save mr-2 text-xl"></i> <?= $isEdit ? 'Guardar Cambios' : 'Crear Plato / Combo' ?>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const rowsContainer = document.getElementById("recipe-rows");
    const priceInput = document.getElementById("price");
    const marginInput = document.getElementById("profit_margin");
    const vesSalePriceSpan = document.getElementById("ves_sale_price");
    let currentCost = 0;
    let priceManuallyEdited = false;

    const unitsByType = {};
    unitsData.forEach(u => {
        if (!unitsByType[u.base_type]) unitsByType[u.base_type] = [];
        unitsByType[u.base_type].push(u);
    });

    function getRowData(row) {
        const sel = row.querySelector(".ing-select");
        const qty = parseFloat(row.querySelector(".ing-qty").value) || 0;
        const unitSel = row.querySelector(".ing-unit");
        
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
                    const containedUnit = unitsData.find(u => u.id == ing.contained_unit_id);
                    if (containedUnit && fromUnit.base_type === containedUnit.base_type && parseFloat(containedUnit.conversion_to_base) > 0) {
                        qtyBase = qty * parseFloat(fromUnit.conversion_to_base);
                        let qtyInContained = qtyBase / parseFloat(containedUnit.conversion_to_base);
                        let contentPerPurchase = parseFloat(ing.content_per_purchase) || 1;
                        qtySale = qtyInContained / contentPerPurchase;
                    } else if (parseFloat(ing.content_per_purchase) > 1 && (fromUnit.base_type === 'peso' || fromUnit.base_type === 'volumen')) {
                        let assumedContentInBase = 0;
                        if (fromUnit.base_type === 'peso') {
                            assumedContentInBase = parseFloat(ing.content_per_purchase) * 1000; 
                        } else if (fromUnit.base_type === 'volumen') {
                            assumedContentInBase = parseFloat(ing.content_per_purchase) * 1000; 
                        }
                        
                        qtyBase = qty * parseFloat(fromUnit.conversion_to_base);
                        let fractionOfContainer = qtyBase / assumedContentInBase;
                        qtySale = fractionOfContainer;
                    } else {
                        qtySale = 0; 
                        qtyBase = 0; 
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
        document.getElementById("live-cost-bs").innerText = fmt(totalCost * window.bcvRate);

        if (!priceManuallyEdited && document.activeElement !== priceInput) {
            const margin = parseFloat(marginInput.value) || 0;
            const newPrice = totalCost * (1 + margin / 100);
            priceInput.value = newPrice > 0 ? newPrice.toFixed(2) : "";
            if (vesSalePriceSpan) vesSalePriceSpan.innerText = "Bs. " + fmt((newPrice > 0 ? newPrice : 0) * window.bcvRate);
        }

        const price = parseFloat(priceInput.value) || 0;
        if (vesSalePriceSpan) vesSalePriceSpan.innerText = "Bs. " + fmt(price * window.bcvRate);

        const profitEl = document.getElementById("live-profit");
        const profitBsEl = document.getElementById("live-profit-bs");
        if (!hasAny || totalCost === 0) {
            profitEl.innerText = "—";
            profitEl.className = "text-slate-400";
            profitBsEl.innerText = "—";
        } else {
            const profit = price - totalCost;
            profitEl.innerText = "$" + fmt(profit);
            profitEl.className = profit >= 0 ? "text-green-600 font-black" : "text-red-500 font-black";
            profitBsEl.innerText = fmt(profit * window.bcvRate);
        }

        const servEl = document.getElementById("live-servings");
        if (!hasAny || servings === Infinity) {
            servEl.innerText = "—";
            servEl.className = "text-slate-400";
        } else {
            servEl.innerText = Math.max(0, servings);
            servEl.className = Math.max(0, servings) > 0 ? "text-brand-600" : "text-red-500";
        }
    }

    function addRow(data = {}) {
        const row = document.createElement("div");
        row.className = "recipe-row flex flex-col sm:flex-row gap-3 items-stretch sm:items-center animate-fade-in-up bg-slate-50 dark:bg-slate-900/40 p-3 rounded-xl border border-slate-200 dark:border-gray-700";

        let ingOptions = '<option value="">-- Elige qué vas a incluir --</option>';
        ingredientsData.forEach(i => {
            const selected = data.ingredient_id == i.id ? "selected" : "";
            let visualUnit = (i.base_type === 'peso') ? 'g' : ((i.base_type === 'volumen') ? 'ml' : 'uds');
            ingOptions += `<option value="${i.id}" ${selected}>${i.name.replace(/</g, '&lt;')} (Inv: ${fmt(i.stock, 0)}${visualUnit})</option>`;
        });

        row.innerHTML = `
            <div class="flex-[3]">
                <select name="ingredient_id[]" class="ing-select w-full rounded-lg border border-slate-200 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm font-bold text-gray-800 dark:text-gray-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer shadow-sm">${ingOptions}</select>
            </div>
            <div class="flex-[1]">
                <input type="number" name="quantity[]" step="0.001" min="0" placeholder="¿Cuánto?" value="${data.quantity ?? ''}" class="ing-qty w-full rounded-lg border border-slate-200 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm font-bold text-gray-800 dark:text-gray-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 shadow-sm text-center">
            </div>
            <div class="flex-[1] relative">
                <select name="unit_id[]" class="ing-unit w-full rounded-lg border border-slate-200 dark:border-gray-600 bg-white dark:bg-slate-800 text-sm font-bold text-gray-800 dark:text-gray-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer shadow-sm"><option value="">Medida...</option></select>
            </div>
            <div class="flex items-center justify-center min-w-[75px]">
                <span class="row-cost text-sm font-bold text-slate-600 dark:text-gray-300 bg-white px-2 py-1.5 rounded-lg border border-slate-200 shadow-sm block w-full text-center">$0.00</span>
            </div>
            <button type="button" class="remove-row text-slate-400 hover:text-red-500 hover:bg-red-50 px-3 py-2 rounded-lg transition-colors shrink-0" title="Quitar elemento"><i class="fas fa-trash-alt"></i></button>
        `;

        const unitSel = row.querySelector(".ing-unit");
        const ingSel = row.querySelector(".ing-select");

        function populateUnits() {
            const ing = ingredientsData.find(i => i.id == ingSel.value);
            unitSel.innerHTML = '<option value="">Medida...</option>';
            if (ing) {
                const families = [
                    ['unidad',  'Por Unidad'],
                    ['peso',    'Por Peso (Ej: g, Kg)'],
                    ['volumen', 'Por Líquido (Ej: ml, L)']
                ];
                const culinaryUnitIds = [1, 2, 4, 9, 3]; 
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
        row.querySelector(".remove-row").addEventListener("click", () => { row.remove(); updateSummary(); });

        rowsContainer.appendChild(row);
        populateUnits();
    }

    document.getElementById("btn-add-row").addEventListener("click", () => addRow());

    marginInput.addEventListener("input", () => {
        priceManuallyEdited = false; 
        updateSummary();
    });

    priceInput.addEventListener("input", function() {
        priceManuallyEdited = true;
        const price = parseFloat(priceInput.value) || 0;
        if (vesSalePriceSpan) vesSalePriceSpan.innerText = "Bs. " + fmt(price * window.bcvRate);
        
        if (currentCost > 0 && price > 0) {
            marginInput.value = (((price - currentCost) / currentCost) * 100).toFixed(2);
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
            if (!ingId && qty <= 0) { row.remove(); return; } 
            if (!ingId || qty <= 0) valid = false;
        });
        
        if (!valid) {
            e.preventDefault();
            Swal.fire("Atención", "Hay elementos incompletos en tu receta (sin cantidad o sin seleccionar). Revísalos.", "warning");
            return;
        }

        if (!this.dataset.confirmed) {
            e.preventDefault();
            Swal.fire({
                title: '¿Todo listo?',
                text: "Los inventarios se descontarán según armaste este combo cada vez que lo vendas.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#ef4444',
                confirmButtonText: '<i class="fas fa-check mr-1"></i> Sí, guardar',
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
