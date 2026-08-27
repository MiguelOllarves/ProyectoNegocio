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

<script>
    const units = <?= json_encode($units ?? []) ?>;
</script>

<datalist id="pack_names">
    <option value="Bulto">
    <option value="Caja">
    <option value="Paquete">
    <option value="Saco">
    <option value="Cesta">
    <option value="Docena">
    <option value="Paca">
</datalist>

<form action="<?= BASE_URL ?>inventory/create" method="POST" enctype="multipart/form-data" class="max-w-7xl mx-auto pb-32 sm:pb-12 px-0 sm:px-6 lg:px-8">
    <div class="flex flex-col md:flex-row gap-4 sm:gap-6 lg:gap-8 mb-8 items-start">
        <!-- COLUMNA IZQUIERDA -->
        <div class="flex-1 flex flex-col gap-4 sm:gap-6 lg:gap-8 min-w-0 md:w-1/2 w-full">
        <!-- Step 1: Información Principal -->
        <div class="bg-white rounded-none sm:rounded-2xl shadow-sm hover:shadow-md transition-shadow border-y sm:border border-gray-100 p-4 sm:p-5 relative overflow-hidden flex flex-col w-full">
            <div class="absolute top-0 left-0 w-2 h-full bg-blue-500"></div>
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm">1</span>
                ¿Qué producto vas a agregar?
            </h3>
            
            <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-xl shadow-sm">
                <p class="text-sm text-blue-900 leading-relaxed"><i class="fas fa-magic text-blue-600 mr-2"></i><b>Concepto Básico:</b> Ponle un nombre claro a tu producto. Categorizarlo te ayudará a formar estantes virtuales ordenados en la caja registradora.</p>
            </div>
            
            <div class="space-y-6">
                <div>
                    <label class="block text-base font-bold text-slate-800 mb-2">Nombre del Producto <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="Ej: Refresco Coca-Cola 2 Litros" class="w-full text-lg rounded-xl border border-slate-200 bg-slate-50 focus:bg-white transition-all shadow-sm px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Categoría</label>
                        <select name="category_id" id="cat_select" class="w-full rounded-xl border border-slate-200 bg-slate-50 focus:bg-white transition-all shadow-sm px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer">
                            <option value="">-- Selecciona una categoría --</option>
                            <?php if(!empty($categories)): foreach($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; endif; ?>
                            <option value="new" class="font-bold text-brand-600">+ Añadir nueva categoría...</option>
                        </select>
                        <input type="text" name="new_category" id="new_category" placeholder="Escribe la nueva categoría" class="hidden mt-2 w-full rounded-md border border-brand-300 bg-brand-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Marca</label>
                        <select name="brand_id" id="brand_select" class="w-full rounded-xl border border-slate-200 bg-slate-50 focus:bg-white transition-all shadow-sm px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer">
                            <option value="">-- Selecciona una marca --</option>
                            <?php if(!empty($brands)): foreach($brands as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; endif; ?>
                            <option value="new" class="font-bold text-brand-600">+ Añadir nueva marca...</option>
                        </select>
                        <input type="text" name="new_brand" id="new_brand" placeholder="Escribe la nueva marca" class="hidden mt-2 w-full rounded-md border border-brand-300 bg-brand-50 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Imagen del Producto (Opcional)</label>
                    <input type="file" name="image" accept="image/*" class="w-full rounded-lg border border-gray-300 px-4 py-2 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors text-sm file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-bold file:bg-brand-100 file:text-brand-700 hover:file:bg-brand-200 cursor-pointer">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Código de Barras (Opcional)</label>
                    <div class="flex">
                        <span class="inline-flex items-center px-4 rounded-l-xl border border-r-0 border-slate-200 bg-slate-100 text-slate-500">
                            <i class="fas fa-barcode text-lg"></i>
                        </span>
                        <input type="text" name="barcode" placeholder="Escanea o escribe el código" class="flex-1 block w-full rounded-none rounded-r-xl border border-slate-200 bg-slate-50 px-4 py-3 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-500 transition-all shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Venta -->
        <div class="bg-white rounded-none sm:rounded-2xl shadow-sm hover:shadow-md transition-shadow border-y sm:border border-gray-100 p-4 sm:p-5 relative overflow-hidden flex flex-col w-full">
            <div class="absolute top-0 left-0 w-2 h-full bg-green-500"></div>
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="bg-green-100 text-green-600 rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm">3</span>
                ¿A cuánto lo vas a vender?
            </h3>
            
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-xl shadow-sm">
                <p class="text-sm text-green-900 leading-relaxed"><i class="fas fa-tags text-green-600 mr-2"></i><b>Ganancia comercial:</b> Dinos a qué precio se lo vas a ofrecer al cliente final. Automáticamente restaremos el costo matemático que llenaste en el paso 2 y te diremos de cuánto es tu porcentaje de ganancia real.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-green-800 mb-2">Precio de Venta (por <span class="lbl_unit_name"></span>)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-green-800 font-bold text-xl">$</span>
                        <input type="number" step="0.01" name="price" id="sale_price" required class="w-full text-2xl font-bold text-green-900 rounded-xl border-2 border-green-300 bg-green-50 pl-10 pr-4 py-3 focus:outline-none focus:bg-white focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all shadow-sm">
                    </div>
                    <div class="mt-2 text-[11px] font-bold text-green-700 opacity-80 pl-2">Equivale a: <span id="ves_sale_price">Bs. 0,00</span> 🇻🇪</div>
                </div>
                
                <div>
                    <div class="flex flex-wrap justify-between sm:justify-start items-center gap-2 mb-2 w-full">
                        <label class="block text-sm font-bold text-slate-700 whitespace-nowrap">% Ganancia</label>
                        <select id="margin_type" class="text-xs bg-transparent text-brand-600 font-bold focus:outline-none cursor-pointer pb-0.5 border-b border-dashed border-brand-200">
                            <option value="margin">Comercial (Costo / %)</option>
                            <option value="markup">Simple (Costo + %)</option>
                        </select>
                    </div>
                    <div class="relative">
                        <input type="number" step="0.01" name="profit_margin" id="profit_margin" value="0.0" class="w-full text-lg rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-500 transition-all shadow-sm">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-500 font-bold">%</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-5 mt-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Moneda</label>
                    <select name="currency" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-500 transition-all shadow-sm">
                        <option value="USD">USD</option>
                        <option value="VES">VES</option>
                        <option value="COP">COP</option>
                    </select>
                </div>
                <div class="flex items-center pt-5">
                    <input type="checkbox" name="is_tax_exempt" id="is_tax_exempt" value="1" class="w-4 h-4 text-brand-600 rounded cursor-pointer">
                    <label for="is_tax_exempt" class="ml-2 text-sm font-bold text-slate-700 cursor-pointer">Exento de IVA (E)</label>
                </div>
            </div>
        </div>

        <!-- Step 4: Inventario -->
        <div class="bg-white rounded-none sm:rounded-2xl shadow-sm hover:shadow-md transition-shadow border-y sm:border border-gray-100 p-4 sm:p-5 relative overflow-hidden flex flex-col w-full">
            <div class="absolute top-0 left-0 w-2 h-full bg-orange-500"></div>
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="bg-orange-100 text-orange-600 rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm">4</span>
                Cantidades en Inventario
            </h3>
            
            <div class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r-xl shadow-sm">
                <p class="text-sm text-orange-900 leading-relaxed"><i class="fas fa-boxes text-orange-600 mr-2"></i><b>Conteo actual:</b> Si acabas de sumar tu negocio y tu caja física tiene mercancía que vas a vender suelta (por ejemplo: si compraste 1 Bulto y trae 20 kilos), esto significa que cuentas con <b>20 individuales</b>.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Inventario Físico (En <span class="lbl_unit_name text-orange-600">Unidad</span>)</label>
                    <input type="number" step="0.001" name="stock" id="stock_containers" value="0" class="w-full text-xl font-bold rounded-xl border-2 border-slate-300 bg-slate-50 px-4 py-3 focus:outline-none focus:bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Stock Mínimo (Alerta en <span class="lbl_unit_name text-orange-600">Unidad</span>)</label>
                    <input type="number" step="0.001" name="min_stock" value="5" class="w-full text-lg rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-500 transition-all shadow-sm">
                </div>
            </div>
            <input type="hidden" id="real_stock" value="0">
            
            <div class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg text-orange-900 font-medium">
                <i class="fas fa-boxes mr-2 text-orange-600"></i> Inventario Total para este producto: <b><span id="calc_total_stock" class="text-xl">0</span> <span class="lbl_unit_name"></span></b>
            </div>
        </div>
        
            </div> <!-- FIN COLUMNA IZQUIERDA -->

        <!-- COLUMNA DERECHA -->
        <div class="flex-1 flex flex-col gap-4 sm:gap-6 lg:gap-8 min-w-0 md:w-1/2 w-full">
        <!-- Step 2: Medición y Compra -->
        <div class="bg-white rounded-none sm:rounded-2xl shadow-sm hover:shadow-md transition-shadow border-y sm:border border-gray-100 p-4 sm:p-5 relative overflow-hidden flex flex-col w-full">
            <div class="absolute top-0 left-0 w-2 h-full bg-indigo-500"></div>
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="bg-indigo-100 text-indigo-600 rounded-full w-8 h-8 flex items-center justify-center mr-3 text-sm">2</span>
                ¿Cómo se mide y se compra?
            </h3>
            
            <div class="mb-6 bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded-r-xl shadow-sm">
                <p class="text-sm text-indigo-900 leading-relaxed"><i class="fas fa-lightbulb text-amber-500 text-lg mr-2"></i><b>¡Presta mucha atención aquí!:</b> A tu negocio pueden entrar <i>Bultos gigantes</i> o <i>Cajas grandes</i>... ¡NO IMPORTA! Aquí arriba <b>solamente debes elegir la unidad más pequeñita</b> en la que lo vas a vender (Unidad, Kilo o Gramo). Más abajo te daremos espacio para crear el Bulto y configurarlo.</p>
            </div>
            
            <!-- SECCIÓN 1: VENTA AL DETAL -->
            <div class="mb-5 border-b border-indigo-100 pb-5">
                <h4 class="font-bold text-indigo-900 text-base mb-1">1. ¿En qué formato le entregas esto al cliente?</h4>
                <p class="text-[11px] text-slate-500 mb-4 leading-tight">Ejemplo: Si vendes Refresco suelto, elige "Por Unidad". Si vendes Queso, elige "Por Peso". Esta será tu Unidad Base de venta.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 bg-indigo-50/30 p-4 rounded-xl border border-indigo-50/50">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Tipo de Medición</label>
                        <select name="measurement_type" id="measurement_type" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-500 cursor-pointer transition-all shadow-sm">
                            <option value="unidad">Por Unidad</option>
                            <option value="peso">Por Peso</option>
                            <option value="volumen">Por Volumen</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Se vende por (Unidad de medida)</label>
                        <select name="sale_unit_id" id="main_unit_id" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-500 cursor-pointer transition-all shadow-sm">
                            <!-- JS will populate with units of selected family -->
                        </select>
                        <!-- Ocultos necesarios para el backend -->
                        <input type="hidden" name="contained_unit_id" id="hidden_contained_unit">
                        <input type="hidden" name="purchase_unit_id" id="hidden_purchase_unit">
                        <input type="hidden" name="base_unit_id" id="hidden_base_unit">
                        
                        <div class="mt-3 flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200" id="fractional_container">
                            <input type="checkbox" name="allow_fractional_sales" id="allow_fractional" value="1" class="w-4 h-4 text-brand-600 rounded cursor-pointer">
                            <label for="allow_fractional" class="text-xs font-bold text-slate-700 cursor-pointer">Permitir venta con decimales (Ej: 0.5 kg)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: EMPAQUES MAYORES -->
            <div class="mb-5 border-b border-indigo-100 pb-5">
                <h4 class="font-bold text-indigo-900 text-base mb-1">2. ¿El proveedor te vende esto en Cajas o Bultos?</h4>
                <p class="text-[11px] text-slate-500 mb-4 leading-tight">Si compras al mayor (Ej: el Bulto y trae 24 refrescos adentro), regístralo aquí. Así el sistema sabrá cómo desarmarlo en tu inventario cuando lo recibas. (Opcional).</p>

                <div class="bg-indigo-50/50 rounded-xl p-4 border border-indigo-100">
                     <div id="presentations-container" class="space-y-3">
                         <!-- Dynamically populated JS rows go here -->
                     </div>
                     <button type="button" id="add-presentation-btn" class="mt-4 w-full sm:w-auto text-sm font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-100 hover:bg-indigo-200 px-5 py-2.5 rounded-lg transition-colors border border-indigo-200 flex justify-center items-center"><i class="fas fa-plus mr-2"></i> Añadir Bulto / Caja</button>
                </div>
            </div>

            <!-- SECCIÓN 3: COSTO -->
            <div>
                <h4 class="font-bold text-indigo-900 text-base mb-1">3. ¿Cuánto te costó comprar esta mercancía?</h4>
                <p class="text-[11px] text-slate-500 mb-4 leading-tight">El sistema necesita saber cuánto te costó exactamente UNA sola <span class="lbl_unit_name font-bold underline"></span> para calcular tus ganancias.</p>
                
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-4">
                    <label class="block text-sm font-bold text-slate-800 mb-2">Costo matemático por 1 <span class="lbl_unit_name text-indigo-700 uppercase underline">Unidad</span> individual:</label>
                    <div class="relative transition-all duration-300 rounded-xl" id="total_cost_wrapper">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-500 font-bold text-lg">$</span>
                        <!-- Usamos el ID total_cost para que la logica inferior y PHP no rompan -->
                        <input type="number" step="0.0001" name="total_cost" id="total_cost" value="0" placeholder="Ej: 1.5" class="w-full text-xl font-bold rounded-xl border border-slate-300 bg-white pl-10 pr-4 py-3 focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm">
                    </div>
                    <div class="mt-2 text-[11px] font-bold text-slate-500 pl-2 transition-all">Equivale a: <span id="ves_math_cost" class="text-slate-700">Bs. 0,00</span> 🇻🇪</div>
                </div>

                <input type="hidden" name="content_per_purchase" id="content_per_purchase" value="1">
                <input type="hidden" name="unit_cost" id="hidden_unit_cost" value="0">
            </div>
        </div>

                </div> <!-- FIN COLUMNA DERECHA -->

    </div> <!-- FIN DEL FLEX ROW -->


    <!-- Extra Options (Barcode & Attributes) -->
    <details class="group mb-10">
        <summary class="flex items-center justify-center p-4 bg-white border border-gray-200 rounded-xl cursor-pointer text-sm font-bold text-gray-600 hover:bg-gray-50 transition-colors shadow-sm">
            <i class="fas fa-sliders-h mr-3 text-brand-500"></i> Opciones Adicionales (Código de Barras, Atributos)
        </summary>
        <div class="mt-4 bg-white rounded-none sm:rounded-2xl shadow-sm hover:shadow-md transition-shadow border-y sm:border border-gray-100 p-5 sm:p-8">
            <!-- (El código de barras ya se movió al Paso 1) -->

            <div class="pt-2">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Atributos Especiales</h3>
                        <p class="text-sm text-slate-500 mt-1">Ideal para agregar Tallas, Colores, Lotes, IMEI, etc.</p>
                    </div>
                    <button type="button" id="add-attr-btn" class="w-full sm:w-auto bg-brand-50 border border-brand-200 text-brand-700 hover:bg-brand-100 hover:border-brand-300 font-bold px-5 py-2.5 rounded-xl transition-all shadow-sm flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i> Añadir Atributo
                    </button>
                </div>
                
                <div id="attributes-container" class="space-y-4">
                    <div class="text-sm text-slate-500 p-6 text-center border-2 border-dashed border-slate-300 rounded-xl bg-slate-50" id="empty-attrs-msg">
                        <i class="fas fa-tags text-2xl text-slate-300 mb-2 block"></i>
                        No has añadido atributos especiales.
                    </div>
                </div>
            </div>
        </div>
    </details>

    <!-- Actions -->
    <div class="mt-8 flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 sm:border-t sm:border-slate-200 sm:pt-6">
        <a href="<?= BASE_URL ?>inventory" class="w-full sm:w-auto px-6 py-4 sm:py-3 bg-slate-100 sm:bg-white border-transparent sm:border-slate-300 border rounded-xl text-slate-700 hover:bg-slate-200 font-bold text-center transition-all">Cancelar</a>
        <button type="submit" class="w-full sm:w-auto px-8 py-4 sm:py-3 bg-brand-600 hover:bg-brand-700 text-white rounded-xl font-bold shadow-lg shadow-brand-500/40 transition-all flex justify-center items-center hover:-translate-y-0.5">
            <i class="fas fa-save mr-2 text-xl"></i> Guardar Producto
        </button>
    </div>
</form>

<script>
    const BCV_RATE = <?= class_exists('Settings') ? Settings::getBcvRate() : 36.5 ?>;

    function formatVes(usd) {
        if (!usd || isNaN(usd)) return "Bs. 0,00";
        return "Bs. " + (usd * BCV_RATE).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Toggle new inputs logic
        const selects = ["cat", "brand"];
        selects.forEach(pref => {
            const select = document.getElementById(pref + "_select");
            const input = document.getElementById("new_" + (pref === "cat" ? "category" : pref));
            if(select && input) {
                select.addEventListener("change", function() {
                    if(this.value === "new") {
                        input.classList.remove("hidden");
                        input.focus();
                    } else {
                        input.classList.add("hidden");
                        input.value = "";
                    }
                });
            }
        });

        // Dynamic Attributes logic
        const btnAdd = document.getElementById("add-attr-btn");
        const container = document.getElementById("attributes-container");
        const emptyMsg = document.getElementById("empty-attrs-msg");

        btnAdd.addEventListener("click", function() {
            if (emptyMsg) emptyMsg.style.display = "none";
            const row = document.createElement("div");
            row.className = "flex gap-3 items-center animate-fade-in-up bg-white p-2 rounded-xl border border-slate-200 shadow-sm";
            row.innerHTML = `
                <div class="flex-1"><input type="text" name="meta_key[]" placeholder="Atributo (Ej: Material)" required class="w-full text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all shadow-sm"></div>
                <div class="flex-1"><input type="text" name="meta_value[]" placeholder="Valor (Ej: Plástico)" required class="w-full text-sm rounded-lg border border-slate-200 bg-slate-50 focus:bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-all shadow-sm"></div>
                <button type="button" class="remove-attr text-slate-400 hover:text-red-500 p-2 focus:outline-none transition-colors" title="Eliminar atributo"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(row);
            row.querySelector(".remove-attr").addEventListener("click", function() {
                container.removeChild(row);
                if (container.children.length === 1 && emptyMsg) { emptyMsg.style.display = "block"; }
            });
        });

        // LOGIC FOR FORM
        const mType = document.getElementById("measurement_type");
        const mainUnit = document.getElementById("main_unit_id");
        const hiddenContained = document.getElementById("hidden_contained_unit");
        const hiddenPurchase = document.getElementById("hidden_purchase_unit");
        const hiddenBase = document.getElementById("hidden_base_unit");
        const allowFrac = document.getElementById("allow_fractional");

        const cType = document.getElementById("container_type");
        const cDetails = document.getElementById("container_details");
        const contentInput = document.getElementById("content_per_purchase");

        const costInput = document.getElementById("total_cost");
        const hiddenCost = document.getElementById("hidden_unit_cost");
        const costSummaryBox = document.getElementById("cost_summary_box");
        const calcCostText = document.getElementById("calc_cost_per_unit");

        const priceInput = document.getElementById("sale_price");
        const marginInput = document.getElementById("profit_margin");

        const stockContainers = document.getElementById("stock_containers");
        const realStock = document.getElementById("real_stock");
        const calcTotalStock = document.getElementById("calc_total_stock");

        let currentCostPerBase = 0;

        const presContainer = document.getElementById("presentations-container");
        const btnAddPres = document.getElementById("add-presentation-btn");
        
        function addPresentationRow(name = '', qty = 1, fixed = false) {
            const row = document.createElement("div");
            row.className = fixed 
                ? "flex items-center animate-fade-in-up bg-indigo-50/80 p-3.5 rounded-xl border border-indigo-200 shadow-inner mb-3" 
                : "flex gap-2 items-center animate-fade-in-up bg-white p-3 rounded-xl border border-indigo-100 shadow-sm";
            
            if (fixed) {
                row.innerHTML = `
                    <div class="w-full relative flex items-center gap-3">
                        <input type="hidden" name="presentation_names[]" value="${name}">
                        <input type="hidden" name="presentation_quantities[]" value="1" class="pres-qty-input">
                        <input type="hidden" name="presentation_units[]" class="pres-unit-input" value="${mainUnit.value}">
                        
                        <div class="bg-indigo-500 text-white w-8 h-8 rounded-full flex items-center justify-center shrink-0 shadow-md">
                            <i class="fas fa-tag text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs font-bold text-indigo-900 leading-tight">Porción Base Individual</p>
                            <p class="text-[10px] text-indigo-600 leading-tight mt-1">Representa 1 <span class="lbl_unit_name font-bold underline decoration-indigo-300"></span> para vender. Sistema Automático.</p>
                        </div>
                        <div class="text-indigo-400 pr-2" title="Configuración Bloqueada por el sistema">
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>
                `;
            } else {
                row.innerHTML = `
                    <div class="flex flex-col sm:flex-row gap-3 w-full items-start sm:items-end">
                        <div class="w-full sm:w-4/12 relative">
                            <label class="block text-xs font-bold text-slate-600 mb-1 shrink-0">Contenedor/Empaque</label>
                            <input type="text" list="pack_names" name="presentation_names[]" value="${name}" required placeholder="Ej: Bulto, Caja..." class="w-full text-sm rounded-lg border border-slate-200 bg-white px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all focus:border-indigo-500">
                        </div>
                        <div class="w-full sm:w-3/12 relative">
                            <label class="block text-xs font-bold text-slate-600 mb-1">¿Cuántas trae?</label>
                            <input type="number" step="0.001" name="presentation_quantities[]" value="${qty}" required class="w-full text-sm font-bold text-slate-700 rounded-lg border border-slate-200 bg-white px-3 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500 pres-qty-input shadow-sm transition-all text-center focus:border-indigo-500">
                        </div>
                        <div class="w-full sm:w-4/12 relative group tooltip-trigger animate-bounce-short">
                            <label class="block text-xs font-bold text-indigo-700 mb-1 flex items-center gap-1 whitespace-nowrap">Total Pagado</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-indigo-500 font-bold">$</span>
                                <input type="number" step="0.01" class="w-full text-sm rounded-lg border border-indigo-200 bg-indigo-50/50 pl-7 pr-2 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none auto-cost-trigger font-bold text-indigo-800 transition-colors placeholder-indigo-300 shadow-sm" placeholder="Ej: 50">
                            </div>
                        </div>
                        <input type="hidden" name="presentation_units[]" class="pres-unit-input" value="${mainUnit.value}">
                        <div class="w-full sm:w-1/12 flex items-center justify-end sm:pb-1">
                            <button type="button" class="remove-pres text-slate-400 hover:text-red-500 p-2.5 bg-white hover:bg-red-50 rounded-lg transition-colors border border-slate-200 shadow-sm" title="Quitar este empaque"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                `;
            }
            presContainer.appendChild(row);
            
            if(!fixed) {
                row.querySelector(".remove-pres").addEventListener("click", () => { presContainer.removeChild(row); });
                
                const costTrigger = row.querySelector('.auto-cost-trigger');
                const qtyInput = row.querySelector('.pres-qty-input');
                costTrigger.addEventListener('input', function() {
                    let totalPaid = parseFloat(this.value) || 0;
                    let qty = parseFloat(qtyInput.value) || 0;
                    if(totalPaid > 0 && qty > 0) {
                        let unitCost = totalPaid / qty;
                        let tc = document.getElementById('total_cost');
                        tc.value = Number(unitCost.toFixed(4));
                        tc.dispatchEvent(new Event('input'));
                        
                        let wrap = document.getElementById('total_cost_wrapper');
                        if(wrap) {
                           wrap.classList.add('bg-yellow-200', 'ring-4', 'ring-yellow-400', 'scale-[1.02]');
                           setTimeout(() => wrap.classList.remove('bg-yellow-200', 'ring-4', 'ring-yellow-400', 'scale-[1.02]'), 600);
                        }

                        let stockInput = document.getElementById('stock_containers');
                        if (stockInput && (stockInput.value === "0" || stockInput.value === "")) {
                            stockInput.value = qty;
                            stockInput.dispatchEvent(new Event('input'));
                            
                            stockInput.classList.add('bg-yellow-100', 'ring-4', 'ring-yellow-300', 'transition-all');
                            setTimeout(() => stockInput.classList.remove('bg-yellow-100', 'ring-4', 'ring-yellow-300'), 600);
                        }
                    }
                });
                qtyInput.addEventListener('input', function() {
                    if (costTrigger.value) costTrigger.dispatchEvent(new Event('input'));
                });
            }
            updateLabels(); 
        }

        btnAddPres.addEventListener("click", () => addPresentationRow('', 1, false));

        function updateLabels() {
            try {
                let unitName = "unidad";
                if (mainUnit && mainUnit.options && mainUnit.options.length > 0 && mainUnit.selectedIndex >= 0) {
                    unitName = mainUnit.options[mainUnit.selectedIndex].text.split(" ")[0] || "unidad";
                }
                document.querySelectorAll(".lbl_unit_name").forEach(el => el.innerText = unitName);
                
                // Actualizar los hiddens de unidad en las presentaciones cada que cambian
                document.querySelectorAll(".pres-unit-input").forEach(el => {
                    el.value = mainUnit.value;
                });
            } catch (e) {
                console.error("Error in updateLabels:", e);
            }
        }

        const units = <?= json_encode(is_array($units) ? $units : []) ?>;

        function renderUnits() {
            try {
                const family = mType ? mType.value : "unidad";
                if (allowFrac) {
                    allowFrac.disabled = (family === "unidad");
                    if (family === "unidad") allowFrac.checked = false;
                }

                let filteredUnits = [];
                const unitsArray = Array.isArray(units) ? units : Object.values(units);
                filteredUnits = unitsArray.filter(u => u.base_type === family);
                
                if (filteredUnits.length === 0 && family === "unidad") {
                    filteredUnits.push({
                        id: "",
                        base_unit_id: "",
                        conversion_to_base: 1,
                        name: "Unidad Estandar",
                        abbreviation: "U"
                    });
                }
                
                let html = "";
                filteredUnits.forEach(u => {
                    html += `<option value="${u.id}" data-base="${u.base_unit_id}" data-conv="${u.conversion_to_base}">${u.name} (${u.abbreviation})</option>`;
                });
                
                if (mainUnit) {
                    mainUnit.innerHTML = html;

                    // Default al cambiar
                    for(let i=0; i<mainUnit.options.length; i++) {
                        let text = mainUnit.options[i].text.toLowerCase();
                        if(family === "peso" && text.includes("(kg)")) { mainUnit.selectedIndex = i; break; }
                        if(family === "volumen" && text.includes("(l)")) { mainUnit.selectedIndex = i; break; }
                    }
                }
                updateUI();
            } catch (e) {
                console.error("Error in renderUnits:", e);
            }
        }

        function updateUI() {
            if(presContainer.children.length === 0) {
                addPresentationRow('Unidad Suelta', 1, true); // Presentacion por defecto
            }
            updateLabels();

            // Set hiddens for backend
            hiddenContained.value = mainUnit.value;
            hiddenPurchase.value = mainUnit.value; 
            
            const opt = mainUnit.options[mainUnit.selectedIndex];
            if(opt) hiddenBase.value = opt.dataset.base;

            calculateEverything();
        }

        function calculateEverything() {
            const unitCost = parseFloat(costInput.value) || 0;
            currentCostPerBase = unitCost;
            hiddenCost.value = currentCostPerBase; 

            // Auto margin/price
            if(document.activeElement !== marginInput && document.activeElement !== priceInput) {
                calculatePrice("cost");
            }

            // Auto stock (ya no lo saca del empaque, es el inventario total el que se provee en base unit)
            const totalUnits = parseFloat(stockContainers.value) || 0;
            calcTotalStock.innerText = totalUnits.toLocaleString();
            
            updateVesHints();
        }

        function updateVesHints() {
            let vMath = document.getElementById('ves_math_cost');
            let vSale = document.getElementById('ves_sale_price');
            if(vMath) vMath.innerText = formatVes(parseFloat(costInput.value) || 0);
            if(vSale) vSale.innerText = formatVes(parseFloat(priceInput.value) || 0);
        }

        function calculatePrice(source) {
            const marginType = document.getElementById("margin_type").value;
            
            if (source === "cost" || source === "margin" || document.activeElement === marginInput || source === "type") {
                const margin = parseFloat(marginInput.value) || 0;
                let newPrice = 0;
                if (currentCostPerBase > 0) {
                    if (marginType === "markup") {
                        newPrice = currentCostPerBase * (1 + (margin / 100));
                    } else { // Comercial
                        newPrice = margin < 100 ? currentCostPerBase / (1 - (margin / 100)) : 0;
                    }
                }
                if(newPrice > 0 || source !== "cost") priceInput.value = newPrice.toFixed(2);
            } else if (source === "price" || document.activeElement === priceInput) {
                const price = parseFloat(priceInput.value) || 0;
                let newMargin = 0;
                if (currentCostPerBase > 0 && price > 0) {
                    if (marginType === "markup") {
                        newMargin = ((price / currentCostPerBase) - 1) * 100;
                    } else { // Comercial
                        newMargin = ((price - currentCostPerBase) / price) * 100;
                    }
                }
                marginInput.value = newMargin.toFixed(2);
            }
            updateVesHints();
        }

        mType.addEventListener("change", renderUnits);
        mainUnit.addEventListener("change", updateUI);
        costInput.addEventListener("input", updateUI);
        stockContainers.addEventListener("input", updateUI);
        
        marginInput.addEventListener("input", () => calculatePrice("margin"));
        priceInput.addEventListener("input", () => calculatePrice("price"));
        document.getElementById("margin_type").addEventListener("change", () => calculatePrice("type"));

        const form = document.querySelector("form");
        form.addEventListener("submit", function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            if (!formData.get("name")) { Swal.fire("Error", "El nombre es obligatorio", "error"); return; }
            if (parseFloat(formData.get("price")) < 0) { Swal.fire("Error", "El precio no puede ser negativo", "error"); return; }
            
            fetch(form.action, { method: "POST", body: formData, headers: { "HX-Request": "true" } })
            .then(async response => {
                if(response.ok) {
                    Swal.fire({ title: "¡Guardado Exitoso!", text: "El producto se ha guardado correctamente.", icon: "success", timer: 2000 })
                    .then(() => { window.location.href = "<?= BASE_URL ?>inventory"; });
                } else {
                    const text = await response.text();
                    Swal.fire("Error al guardar", text || "Hubo un problema al guardar", "error");
                }
            })
            .catch(() => Swal.fire("Error", "Hubo un error de red al guardar.", "error"));
        });

        renderUnits();
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